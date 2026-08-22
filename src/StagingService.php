<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging;

use Ksfraser\ImportStaging\DTO\StagingTransactionDTO;
use Ksfraser\ImportStaging\DTO\StagingItemDTO;
use Ksfraser\ImportStaging\DTO\StagingPaymentDTO;
use Ksfraser\ImportStaging\DTO\StagingResult;
use Ksfraser\ImportStaging\DTO\StagingError;

/**
 * Service for staging transactions, items, and payments into ISU staging tables.
 *
 * Provides two interfaces:
 *   - DTO-based (preferred): stageTransactionDTO(), stageItemDTO(), stagePaymentDTO()
 *   - Array-based (legacy): stageTransaction(), stageItem(), stagePayment()
 *
 * Both interfaces validate, deduplicate, and insert/update via the legacy
 * model classes. The DTO interface returns typed StagingResult objects.
 *
 * @since 2.0.0
 * @since 2.1.0 Added DTO-based methods
 */
class StagingService
{
    // =========================================================================
    // DTO-based API (preferred)
    // =========================================================================

    /**
     * Stage a transaction from a typed DTO.
     *
     * 1. Validate required fields
     * 2. Deduplicate on sourceTransactionId
     * 3. If exists + identical fields → return duplicate
     * 4. If exists + fields differ → update → return updated
     * 5. If not found → insert → return inserted
     * 6. On error → return error with StagingError
     *
     * @since 2.1.0
     */
    public function stageTransactionDTO(StagingTransactionDTO $dto): StagingResult
    {
        $validationError = $dto->validate();
        if ($validationError !== null) {
            return StagingResult::error($validationError);
        }

        if (!function_exists('db_query')) {
            return StagingResult::error(new StagingError(
                StagingError::FA_NOT_AVAILABLE,
                'FA database layer not available'
            ));
        }

        $txnId = $dto->getSourceTransactionId();

        // Check for existing record
        $existing = $this->findExistingTransaction($txnId);
        if ($existing !== false) {
            if ($this->transactionFieldsIdentical($dto, $existing)) {
                return StagingResult::duplicate((int)$existing['id']);
            }
            // Fields differ — update
            $updated = $this->updateTransaction((int)$existing['id'], $dto);
            if ($updated) {
                return StagingResult::updated((int)$existing['id']);
            }
            return StagingResult::error(new StagingError(
                StagingError::DB_ERROR,
                'Update failed for transaction: ' . $txnId,
                'sourceTransactionId',
                ['db_error' => db_error()]
            ));
        }

        // Insert new
        $id = $this->insertTransaction($dto);
        if ($id !== false) {
            return StagingResult::inserted((int)$id);
        }

        return StagingResult::error(new StagingError(
            StagingError::DB_ERROR,
            'Insert failed for transaction: ' . $txnId,
            'sourceTransactionId',
            ['db_error' => db_error()]
        ));
    }

    /**
     * Stage an item from a typed DTO.
     *
     * Items are additive — no deduplication. Always inserts.
     *
     * @since 2.1.0
     */
    public function stageItemDTO(StagingItemDTO $dto): StagingResult
    {
        $validationError = $dto->validate();
        if ($validationError !== null) {
            return StagingResult::error($validationError);
        }

        if (!function_exists('db_query')) {
            return StagingResult::error(new StagingError(
                StagingError::FA_NOT_AVAILABLE,
                'FA database layer not available'
            ));
        }

        $id = $this->insertItem($dto);
        if ($id !== false) {
            return StagingResult::inserted((int)$id);
        }

        return StagingResult::error(new StagingError(
            StagingError::DB_ERROR,
            'Insert failed for item (transaction: ' . $dto->getSourceTransactionId() . ')',
            'sourceTransactionId',
            ['db_error' => db_error()]
        ));
    }

    /**
     * Stage a payment-match record from a typed DTO.
     *
     * Deduplicates on paymentId (UNIQUE index).
     *
     * @since 2.1.0
     */
    public function stagePaymentDTO(StagingPaymentDTO $dto): StagingResult
    {
        $validationError = $dto->validate();
        if ($validationError !== null) {
            return StagingResult::error($validationError);
        }

        if (!function_exists('db_query')) {
            return StagingResult::error(new StagingError(
                StagingError::FA_NOT_AVAILABLE,
                'FA database layer not available'
            ));
        }

        // Check for existing record
        $existing = $this->findExistingPayment($dto->getPaymentId());
        if ($existing !== false) {
            return StagingResult::duplicate((int)$existing['square_import_payments_id']);
        }

        $id = $this->insertPayment($dto);
        if ($id !== false) {
            return StagingResult::inserted((int)$id);
        }

        return StagingResult::error(new StagingError(
            StagingError::DB_ERROR,
            'Insert failed for payment: ' . $dto->getPaymentId(),
            'paymentId',
            ['db_error' => db_error()]
        ));
    }

    /**
     * Update the status of a staged transaction.
     *
     * @param int $id Staging ID
     * @param string $status New status
     * @param array $extra Additional fields to update
     * @return StagingResult
     * @since 2.1.0
     */
    public function updateStatus(int $id, string $status, array $extra = []): StagingResult
    {
        if ($id <= 0 || $status === '') {
            return StagingResult::error(new StagingError(
                StagingError::VALIDATION_FAILED,
                'Missing id or status'
            ));
        }

        if (!function_exists('db_query')) {
            return StagingResult::error(new StagingError(
                StagingError::FA_NOT_AVAILABLE,
                'FA database layer not available'
            ));
        }

        $table = TB_PREF . 'ksf_import_square_transactions';
        $sets = ["`status` = '" . db_escape($status) . "'"];

        foreach ($extra as $field => $value) {
            if ($field === 'error_log') {
                $sets[] = "`error_log` = '" . db_escape((string)$value) . "'";
            } elseif ($field === 'fa_invoice_no') {
                $sets[] = "`fa_invoice_no` = " . (int)$value;
            } elseif ($field === 'fa_debtor_no') {
                $sets[] = "`fa_debtor_no` = " . (int)$value;
            } elseif ($field === 'fa_branch_code') {
                $sets[] = "`fa_branch_code` = '" . db_escape((string)$value) . "'";
            }
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE id = " . (int)$id;
        $result = @db_query($sql);

        if ($result !== false) {
            return StagingResult::updated($id);
        }

        return StagingResult::error(new StagingError(
            StagingError::DB_ERROR,
            'Update failed: ' . db_error()
        ));
    }

    // =========================================================================
    // Private helpers — DB operations
    // =========================================================================

    /**
     * Find existing transaction by sourceTransactionId.
     *
     * @return array|false Row array or false if not found
     */
    private function findExistingTransaction(string $transactionId)
    {
        if (!function_exists('db_query')) {
            return false;
        }

        $table = defined('TB_PREF') ? TB_PREF : '0_';
        $table .= 'ksf_import_square_transactions';
        $sql = "SELECT id, transaction_id, gross_sales, discounts, net_sales, tax, tip, total_collected"
             . " FROM {$table}"
             . " WHERE transaction_id = '" . db_escape($transactionId) . "'"
             . " LIMIT 1";
        $result = @db_query($sql);
        if ($result !== false && db_num_rows($result) > 0) {
            return db_fetch_assoc($result);
        }
        return false;
    }

    /**
     * Compare DTO fields against existing DB row to detect changes.
     */
    private function transactionFieldsIdentical(StagingTransactionDTO $dto, array $existing): bool
    {
        $floatFields = [
            'gross_sales', 'discounts', 'net_sales', 'tax', 'tip', 'total_collected',
        ];
        foreach ($floatFields as $field) {
            $camel = str_replace('_', '', $field);
            $dtoValue = (float)$dto->{"get" . ucfirst($camel)}();
            $dbValue = (float)($existing[$field] ?? 0);
            if (abs($dtoValue - $dbValue) > 0.005) {
                return false;
            }
        }
        return true;
    }

    /**
     * Insert a new transaction row from a DTO.
     *
     * @return int|false New row ID or false on failure
     */
    private function insertTransaction(StagingTransactionDTO $dto)
    {
        require_once(__DIR__ . '/../class.ksf_import_square_transactions.php');
        $model = new \ksf_import_square_transactions_model();

        $data = $dto->toArray();
        $fieldMap = $this->getTransactionFieldMap();
        foreach ($fieldMap as $dbField => $modelField) {
            if (isset($data[$dbField]) && $data[$dbField] !== '') {
                $model->set($modelField, $data[$dbField]);
            }
        }

        if ($data['raw_json'] ?? '' !== '') {
            $model->set('raw_json', $data['raw_json']);
        }

        return $model->insert();
    }

    /**
     * Update an existing transaction row from a DTO.
     *
     * @return bool Success
     */
    private function updateTransaction(int $id, StagingTransactionDTO $dto): bool
    {
        $table = TB_PREF . 'ksf_import_square_transactions';
        $data = $dto->toArray();
        $sets = [];

        $updatable = [
            'gross_sales', 'discounts', 'net_sales', 'tax', 'tip', 'total_collected',
            'partial_refunds', 'card', 'cash', 'fees', 'net_total',
            'source', 'card_entry_methods', 'card_brand', 'device_name',
            'staff_name', 'staff_id', 'description', 'details',
            'event_type', 'location', 'Dining_option',
            'Customer_id', 'customer_name', 'customer_reference_id',
            'transaction_status', 'deposit_id', 'deposit_date',
        ];

        foreach ($updatable as $field) {
            if (isset($data[$field])) {
                $sets[] = "`{$field}` = '" . db_escape((string)$data[$field]) . "'";
            }
        }

        if (empty($sets)) {
            return true;
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE id = " . (int)$id;
        $result = @db_query($sql);
        return $result !== false;
    }

    /**
     * Find existing payment by paymentId.
     *
     * @return array|false
     */
    private function findExistingPayment(string $paymentId)
    {
        if (!function_exists('db_query')) {
            return false;
        }

        $table = (defined('TB_PREF') ? TB_PREF : '0_') . 'ksf_import_square_payments';
        $sql = "SELECT square_import_payments_id FROM {$table}"
             . " WHERE square_payment_id = '" . db_escape($paymentId) . "'"
             . " LIMIT 1";
        $result = @db_query($sql);
        if ($result !== false && db_num_rows($result) > 0) {
            return db_fetch_assoc($result);
        }
        return false;
    }

    /**
     * Insert a new payment row from a DTO.
     *
     * @return int|false
     */
    private function insertPayment(StagingPaymentDTO $dto)
    {
        require_once(__DIR__ . '/../class.ksf_import_square_payments.php');
        $model = new \ksf_import_square_payments_model();

        $model->set('square_payment_id', $dto->getPaymentId());
        $model->set('total_collected', $dto->getTotalCollected());
        $model->set('trans_type', $dto->getTransType());
        $model->set('trans_no', $dto->getTransNo());

        return $model->insert();
    }

    /**
     * Insert a new item row from a DTO.
     *
     * @return int|false
     */
    private function insertItem(StagingItemDTO $dto)
    {
        require_once(__DIR__ . '/../class.ksf_import_square_items.php');
        $model = new \ksf_import_square_items_model();

        $data = $dto->toArray();
        $fieldMap = $this->getItemFieldMap();
        foreach ($fieldMap as $dbField => $modelField) {
            if (isset($data[$dbField]) && $data[$dbField] !== '') {
                $model->set($modelField, $data[$dbField]);
            }
        }

        return $model->insert();
    }

    /**
     * Get the field mapping from DB column names to ISU model field names.
     *
     * @return array<string, string>
     */
    private function getTransactionFieldMap(): array
    {
        return [
            'source'                => 'source',
            'Date'                  => 'Date',
            'Time'                  => 'Time',
            'Timezone'              => 'Timezone',
            'gross_sales'           => 'gross_sales',
            'discounts'             => 'discounts',
            'service_charges'       => 'service_charges',
            'gift_card_sales'       => 'gift_card_sales',
            'net_sales'             => 'net_sales',
            'tax'                   => 'tax',
            'tip'                   => 'tip',
            'partial_refunds'       => 'partial_refunds',
            'total_collected'       => 'total_collected',
            'card'                  => 'card',
            'card_entry_methods'    => 'card_entry_methods',
            'cash'                  => 'cash',
            'square_gift_card'      => 'square_gift_card',
            'other_tender'          => 'other_tender',
            'other_tender_type'     => 'other_tender_type',
            'other_tender_note'     => 'other_tender_note',
            'fees'                  => 'fees',
            'net_total'             => 'net_total',
            'transaction_id'        => 'transaction_id',
            'payment_id'            => 'payment_id',
            'card_brand'            => 'card_brand',
            'PAN_suffix'            => 'PAN_suffix',
            'device_name'           => 'device_name',
            'staff_name'            => 'staff_name',
            'staff_id'              => 'staff_id',
            'description'           => 'description',
            'details'               => 'details',
            'event_type'            => 'event_type',
            'location'              => 'location',
            'Dining_option'         => 'Dining_option',
            'Customer_id'           => 'Customer_id',
            'customer_name'         => 'customer_name',
            'customer_reference_id' => 'customer_reference_id',
            'device_nickname'       => 'device_nickname',
            'third_party_fees'      => 'third_party_fees',
            'deposit_id'            => 'deposit_id',
            'deposit_date'          => 'deposit_date',
            'deposit_details'       => 'deposit_details',
            'fee_percentage_rate'   => 'fee_percentage_rate',
            'fee_fixed_rate'        => 'fee_fixed_rate',
            'refund_reason'         => 'refund_reason',
            'discount_name'         => 'discount_name',
            'transaction_status'    => 'transaction_status',
            'order_reference_id'    => 'order_reference_id',
            'fulfillment_note'      => 'fulfillment_note',
            'free_processing_applied' => 'free_processing_applied',
        ];
    }

    /**
     * Get the field mapping for items.
     *
     * @return array<string, string>
     */
    private function getItemFieldMap(): array
    {
        return [
            'Date'                  => 'Date',
            'Time'                  => 'Time',
            'Timezone'              => 'Timezone',
            'Category'              => 'Category',
            'Item'                  => 'Item',
            'Price_Point_Name'      => 'Price_Point_Name',
            'stock_id'              => 'stock_id',
            'modifiers_applied'     => 'modifiers_applied',
            'quantity'              => 'quantity',
            'gross_sales'           => 'gross_sales',
            'discounts'             => 'discounts',
            'net_sales'             => 'net_sales',
            'tax'                   => 'tax',
            'transaction_id'        => 'transaction_id',
            'payment_id'            => 'payment_id',
            'device_name'           => 'device_name',
            'notes'                 => 'notes',
            'details'               => 'details',
            'event_type'            => 'event_type',
            'location'              => 'location',
            'dining_option'         => 'dining_option',
            'Customer_id'           => 'Customer_id',
            'customer_name'         => 'customer_name',
            'customer_reference_id' => 'customer_reference_id',
            'unit'                  => 'unit',
            'count'                 => 'count',
            'itemization_type'      => 'itemization_type',
            'fulfillment_note'      => 'fulfillment_note',
        ];
    }
}
