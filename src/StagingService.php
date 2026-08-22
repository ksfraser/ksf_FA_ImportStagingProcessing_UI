<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging;

/**
 * Service for staging transactions, items, and payments into ISU staging tables.
 *
 * Wraps the legacy DAO models and provides a clean hook interface for
 * importing modules (Square, Woo, etc.) to stage data via hook_invoke.
 *
 * @since 2.0.0
 */
class StagingService
{
    /**
     * Stage a transaction into the ksf_import_square_transactions table.
     *
     * Accepts a normalized transaction array from any source module.
     * Required keys: source_transaction_id, total_amount
     *
     * @param array $data Transaction data (keys match ISU column names)
     * @return array ['success' => bool, 'id' => int, 'error' => string]
     */
    public function stageTransaction(array $data): array
    {
        $txnId = $data['source_transaction_id'] ?? $data['transaction_id'] ?? '';
        if ($txnId === '') {
            return ['success' => false, 'id' => 0, 'error' => 'Missing source_transaction_id'];
        }

        if (!function_exists('db_query')) {
            return ['success' => false, 'id' => 0, 'error' => 'FA not available'];
        }

        if ($this->transactionExists($txnId)) {
            return ['success' => false, 'id' => 0, 'error' => 'Already staged: ' . $txnId];
        }

        require_once(__DIR__ . '/../class.ksf_import_square_transactions.php');
        $model = new \ksf_import_square_transactions_model();

        $fieldMap = $this->getTransactionFieldMap();
        foreach ($fieldMap as $sourceKey => $modelField) {
            if (isset($data[$sourceKey]) && $data[$sourceKey] !== '') {
                $model->set($modelField, $data[$sourceKey]);
            }
        }

        if (isset($data['raw_json'])) {
            $model->set('raw_json', $data['raw_json']);
        }

        $id = $model->insert();
        if ($id) {
            return ['success' => true, 'id' => (int)$id, 'error' => ''];
        }

        return ['success' => false, 'id' => 0, 'error' => 'Insert failed'];
    }

    /**
     * Stage a line item into the ksf_import_square_items table.
     *
     * @param array $data Item data
     * @return array ['success' => bool, 'id' => int, 'error' => string]
     */
    public function stageItem(array $data): array
    {
        $txnId = $data['transaction_id'] ?? $data['payment_id'] ?? '';
        if ($txnId === '') {
            return ['success' => false, 'id' => 0, 'error' => 'Missing transaction_id'];
        }

        if (!function_exists('db_query')) {
            return ['success' => false, 'id' => 0, 'error' => 'FA not available'];
        }

        require_once(__DIR__ . '/../class.ksf_import_square_items.php');
        $model = new \ksf_import_square_items_model();

        $fieldMap = $this->getItemFieldMap();
        foreach ($fieldMap as $sourceKey => $modelField) {
            if (isset($data[$sourceKey]) && $data[$sourceKey] !== '') {
                $model->set($modelField, $data[$sourceKey]);
            }
        }

        $id = $model->insert();
        if ($id) {
            return ['success' => true, 'id' => (int)$id, 'error' => ''];
        }

        return ['success' => false, 'id' => 0, 'error' => 'Insert failed'];
    }

    /**
     * Stage a payment match record.
     *
     * @param array $data Payment data
     * @return array ['success' => bool, 'id' => int, 'error' => string]
     */
    public function stagePayment(array $data): array
    {
        $txnId = $data['transaction_id'] ?? $data['payment_id'] ?? '';
        if ($txnId === '') {
            return ['success' => false, 'id' => 0, 'error' => 'Missing transaction_id'];
        }

        if (!function_exists('db_query')) {
            return ['success' => false, 'id' => 0, 'error' => 'FA not available'];
        }

        require_once(__DIR__ . '/../class.ksf_import_square_payments.php');
        $model = new \ksf_import_square_payments_model();

        foreach ($data as $key => $value) {
            $model->set($key, $value);
        }

        $id = $model->insert();
        if ($id) {
            return ['success' => true, 'id' => (int)$id, 'error' => ''];
        }

        return ['success' => false, 'id' => 0, 'error' => 'Insert failed'];
    }

    /**
     * Update the status of a staged transaction.
     *
     * @param int $id Staging ID
     * @param string $status New status
     * @param array $extra Additional fields to update
     * @return array ['success' => bool, 'error' => string]
     */
    public function updateStatus(int $id, string $status, array $extra = []): array
    {
        if ($id <= 0 || $status === '') {
            return ['success' => false, 'error' => 'Missing id or status'];
        }

        if (!function_exists('db_query')) {
            return ['success' => false, 'error' => 'FA not available'];
        }

        $table = TB_PREF . 'ksf_import_square_transactions';
        $sets = ["`status` = '" . \db_escape($status) . "'"];

        foreach ($extra as $field => $value) {
            if ($field === 'error_log') {
                $sets[] = "`error_log` = '" . \db_escape((string)$value) . "'";
            } elseif ($field === 'fa_invoice_no') {
                $sets[] = "`fa_invoice_no` = " . (int)$value;
            } elseif ($field === 'fa_debtor_no') {
                $sets[] = "`fa_debtor_no` = " . (int)$value;
            } elseif ($field === 'fa_branch_code') {
                $sets[] = "`fa_branch_code` = '" . \db_escape((string)$value) . "'";
            }
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE id = " . (int)$id;
        $result = @\db_query($sql);

        if ($result !== false) {
            return ['success' => true, 'error' => ''];
        }

        return ['success' => false, 'error' => 'Update failed: ' . \db_error()];
    }

    /**
     * Check if a transaction is already staged.
     *
     * @param string $transactionId Source transaction ID
     * @return bool
     */
    public function transactionExists(string $transactionId): bool
    {
        if (!function_exists('db_query')) {
            return false;
        }

        $table = \defined('TB_PREF') ? \TB_PREF : '0_';
        $table .= 'ksf_import_square_transactions';
        $sql = "SELECT COUNT(*) AS cnt FROM {$table} WHERE transaction_id = '" . \db_escape($transactionId) . "'";
        $result = @\db_query($sql);
        if ($result !== false) {
            $row = \db_fetch_assoc($result);
            return (int)$row['cnt'] > 0;
        }
        return false;
    }

    /**
     * Get the field mapping from normalized keys to ISU model fields.
     *
     * @return array<string, string>
     */
    private function getTransactionFieldMap(): array
    {
        return [
            'source'               => 'source',
            'Date'                 => 'Date',
            'date'                 => 'Date',
            'Time'                 => 'Time',
            'time'                 => 'Time',
            'Timezone'             => 'Timezone',
            'transaction_id'       => 'transaction_id',
            'source_transaction_id' => 'transaction_id',
            'payment_id'           => 'payment_id',
            'square_order_id'      => 'square_order_id',
            'square_location_id'   => 'square_location_id',
            'location'             => 'location',
            'environment'          => 'environment',
            'status'               => 'status',
            'gross_sales'          => 'gross_sales',
            'net_sales'            => 'net_sales',
            'total_collected'      => 'total_collected',
            'tax'                  => 'tax',
            'tip'                  => 'tip',
            'partial_refunds'      => 'partial_refunds',
            'card'                 => 'card',
            'card_entry_methods'   => 'card_entry_methods',
            'card_brand'           => 'card_brand',
            'PAN_suffix'           => 'PAN_suffix',
            'cash'                 => 'cash',
            'fees'                 => 'fees',
            'net_total'            => 'net_total',
            'customer_name'        => 'customer_name',
            'customer_email'       => 'customer_name',
            'Customer_id'          => 'Customer_id',
            'square_customer_id'   => 'Customer_id',
            'customer_reference_id' => 'customer_reference_id',
            'deposit_id'           => 'deposit_id',
            'deposit_date'         => 'deposit_date',
            'deposit_details'      => 'deposit_details',
            'order_reference_id'   => 'order_reference_id',
            'transaction_status'   => 'transaction_status',
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
            'Date'                 => 'Date',
            'date'                 => 'Date',
            'Time'                 => 'Time',
            'Timezone'             => 'Timezone',
            'transaction_id'       => 'transaction_id',
            'payment_id'           => 'payment_id',
            'Item'                 => 'Item',
            'item'                 => 'Item',
            'name'                 => 'Item',
            'quantity'             => 'quantity',
            'sku'                  => 'sku',
            'stock_id'             => 'stock_id',
            'location'             => 'location',
            'gross_sales'          => 'gross_sales',
            'net_sales'            => 'net_sales',
            'tax'                  => 'tax',
            'discounts'            => 'discounts',
            'unit_price'           => 'unit_price',
            'total_amount'         => 'total_amount',
            'discount_amount'      => 'discount_amount',
        ];
    }
}
