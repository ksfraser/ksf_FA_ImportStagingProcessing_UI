<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\DTO;

/**
 * DTO for staging a single transaction row.
 *
 * Maps 1:1 to the 0_ksf_import_square_transactions table columns.
 * Source modules (Square, Woo, etc.) populate this from their API/CSV
 * data and pass it to ISU via hook_invoke('staging:stageTransaction').
 *
 * Required field: sourceTransactionId (non-empty).
 *
 * @since 2.1.0
 */
class StagingTransactionDTO
{
    /** @var string Source module identifier (e.g., 'square', 'woocommerce') */
    private $source = '';

    /** @var string Transaction date (YYYY-MM-DD) */
    private $date = '';

    /** @var string Transaction time (HH:MM:SS) */
    private $time = '';

    /** @var string Timezone string */
    private $timezone = '';

    /** @var float Gross sales amount */
    private $grossSales = 0.0;

    /** @var float Discount amount */
    private $discounts = 0.0;

    /** @var float Service charges */
    private $serviceCharges = 0.0;

    /** @var float Gift card sales */
    private $giftCardSales = 0.0;

    /** @var float Net sales amount */
    private $netSales = 0.0;

    /** @var float Tax amount */
    private $tax = 0.0;

    /** @var float Tip amount */
    private $tip = 0.0;

    /** @var float Partial refund amount */
    private $partialRefunds = 0.0;

    /** @var float Total collected amount */
    private $totalCollected = 0.0;

    /** @var string Payment source/tender type */
    private $sourceTender = '';

    /** @var float Card payment amount */
    private $card = 0.0;

    /** @var string Card entry methods */
    private $cardEntryMethods = '';

    /** @var float Cash payment amount */
    private $cash = 0.0;

    /** @var float Square gift card amount */
    private $squareGiftCard = 0.0;

    /** @var float Other tender amount */
    private $otherTender = 0.0;

    /** @var string Other tender type */
    private $otherTenderType = '';

    /** @var string Other tender note */
    private $otherTenderNote = '';

    /** @var float Processing fees */
    private $fees = 0.0;

    /** @var float Net total after fees */
    private $netTotal = 0.0;

    /** @var string Source transaction ID (required, unique key for dedup) */
    private $sourceTransactionId = '';

    /** @var string Source payment ID */
    private $paymentId = '';

    /** @var string Card brand (Visa, MC, etc.) */
    private $cardBrand = '';

    /** @var int Card PAN suffix */
    private $panSuffix = 0;

    /** @var string Device name */
    private $deviceName = '';

    /** @var string Staff name */
    private $staffName = '';

    /** @var string Staff ID */
    private $staffId = '';

    /** @var string Transaction description */
    private $description = '';

    /** @var string Additional details */
    private $details = '';

    /** @var string Event type */
    private $eventType = '';

    /** @var string Location identifier */
    private $location = '';

    /** @var string Dining option */
    private $diningOption = '';

    /** @var int Source customer ID */
    private $customerId = 0;

    /** @var string Customer name */
    private $customerName = '';

    /** @var string Customer reference ID */
    private $customerReferenceId = '';

    /** @var string Device nickname */
    private $deviceNickname = '';

    /** @var float Third-party fees */
    private $thirdPartyFees = 0.0;

    /** @var string Deposit ID */
    private $depositId = '';

    /** @var string Deposit date (YYYY-MM-DD) */
    private $depositDate = '';

    /** @var string Deposit details */
    private $depositDetails = '';

    /** @var float Fee percentage rate */
    private $feePercentageRate = 0.0;

    /** @var float Fee fixed rate */
    private $feeFixedRate = 0.0;

    /** @var string Refund reason */
    private $refundReason = '';

    /** @var string Discount name */
    private $discountName = '';

    /** @var string Transaction status */
    private $transactionStatus = '';

    /** @var string Order reference ID */
    private $orderReferenceId = '';

    /** @var string Fulfillment note */
    private $fulfillmentNote = '';

    /** @var float Free processing applied */
    private $freeProcessingApplied = 0.0;

    /** @var string Raw JSON from source API */
    private $rawJson = '';

    /** @var int Source-specific location ID (for filtering, not stored in table) */
    private $squareLocationId = 0;

    // -- Factory ------------------------------------------------------------

    /**
     * Create from an associative array (e.g., CSV row or API response).
     *
     * Accepts both camelCase keys and legacy snake_case column names.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $map = self::keyMap();

        foreach ($data as $key => $value) {
            $camel = $map[$key] ?? $key;
            if (property_exists($dto, $camel)) {
                $dto->$camel = $value;
            }
        }

        return $dto;
    }

    // -- Serialization ------------------------------------------------------

    /**
     * Export to associative array using DB column names (snake_case).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source'                   => $this->source,
            'Date'                     => $this->date,
            'Time'                     => $this->time,
            'Timezone'                 => $this->timezone,
            'gross_sales'              => $this->grossSales,
            'discounts'                => $this->discounts,
            'service_charges'          => $this->serviceCharges,
            'gift_card_sales'          => $this->giftCardSales,
            'net_sales'                => $this->netSales,
            'tax'                      => $this->tax,
            'tip'                      => $this->tip,
            'partial_refunds'          => $this->partialRefunds,
            'total_collected'          => $this->totalCollected,
            'card'                     => $this->card,
            'card_entry_methods'       => $this->cardEntryMethods,
            'cash'                     => $this->cash,
            'square_gift_card'         => $this->squareGiftCard,
            'other_tender'             => $this->otherTender,
            'other_tender_type'        => $this->otherTenderType,
            'other_tender_note'        => $this->otherTenderNote,
            'fees'                     => $this->fees,
            'net_total'                => $this->netTotal,
            'transaction_id'           => $this->sourceTransactionId,
            'payment_id'               => $this->paymentId,
            'card_brand'               => $this->cardBrand,
            'PAN_suffix'               => $this->panSuffix,
            'device_name'              => $this->deviceName,
            'staff_name'               => $this->staffName,
            'staff_id'                 => $this->staffId,
            'description'              => $this->description,
            'details'                  => $this->details,
            'event_type'               => $this->eventType,
            'location'                 => $this->location,
            'Dining_option'            => $this->diningOption,
            'Customer_id'              => $this->customerId,
            'customer_name'            => $this->customerName,
            'customer_reference_id'    => $this->customerReferenceId,
            'device_nickname'          => $this->deviceNickname,
            'third_party_fees'         => $this->thirdPartyFees,
            'deposit_id'               => $this->depositId,
            'deposit_date'             => $this->depositDate,
            'deposit_details'          => $this->depositDetails,
            'fee_percentage_rate'      => $this->feePercentageRate,
            'fee_fixed_rate'           => $this->feeFixedRate,
            'refund_reason'            => $this->refundReason,
            'discount_name'            => $this->discountName,
            'transaction_status'       => $this->transactionStatus,
            'order_reference_id'       => $this->orderReferenceId,
            'fulfillment_note'         => $this->fulfillmentNote,
            'free_processing_applied'  => $this->freeProcessingApplied,
            'square_location_id'       => $this->squareLocationId,
            'raw_json'                 => $this->rawJson,
        ];
    }

    // -- Validation ---------------------------------------------------------

    /**
     * Validate required fields. Returns a StagingError on failure, null on success.
     *
     * @return StagingError|null
     */
    public function validate(): ?StagingError
    {
        if ($this->sourceTransactionId === '') {
            return new StagingError(
                StagingError::MISSING_REQUIRED,
                'source_transaction_id is required',
                'sourceTransactionId'
            );
        }

        if ($this->date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->date)) {
            return new StagingError(
                StagingError::INVALID_FORMAT,
                'Date must be YYYY-MM-DD format, got: ' . $this->date,
                'date'
            );
        }

        return null;
    }

    // -- Getters (camelCase) ------------------------------------------------

    public function getSource(): string { return $this->source; }
    public function getDate(): string { return $this->date; }
    public function getTime(): string { return $this->time; }
    public function getTimezone(): string { return $this->timezone; }
    public function getGrossSales(): float { return $this->grossSales; }
    public function getDiscounts(): float { return $this->discounts; }
    public function getServiceCharges(): float { return $this->serviceCharges; }
    public function getGiftCardSales(): float { return $this->giftCardSales; }
    public function getNetSales(): float { return $this->netSales; }
    public function getTax(): float { return $this->tax; }
    public function getTip(): float { return $this->tip; }
    public function getPartialRefunds(): float { return $this->partialRefunds; }
    public function getTotalCollected(): float { return $this->totalCollected; }
    public function getSourceTender(): string { return $this->sourceTender; }
    public function getCard(): float { return $this->card; }
    public function getCardEntryMethods(): string { return $this->cardEntryMethods; }
    public function getCash(): float { return $this->cash; }
    public function getSquareGiftCard(): float { return $this->squareGiftCard; }
    public function getOtherTender(): float { return $this->otherTender; }
    public function getOtherTenderType(): string { return $this->otherTenderType; }
    public function getOtherTenderNote(): string { return $this->otherTenderNote; }
    public function getFees(): float { return $this->fees; }
    public function getNetTotal(): float { return $this->netTotal; }
    public function getSourceTransactionId(): string { return $this->sourceTransactionId; }
    public function getPaymentId(): string { return $this->paymentId; }
    public function getCardBrand(): string { return $this->cardBrand; }
    public function getPanSuffix(): int { return $this->panSuffix; }
    public function getDeviceName(): string { return $this->deviceName; }
    public function getStaffName(): string { return $this->staffName; }
    public function getStaffId(): string { return $this->staffId; }
    public function getDescription(): string { return $this->description; }
    public function getDetails(): string { return $this->details; }
    public function getEventType(): string { return $this->eventType; }
    public function getLocation(): string { return $this->location; }
    public function getDiningOption(): string { return $this->diningOption; }
    public function getCustomerId(): int { return $this->customerId; }
    public function getCustomerName(): string { return $this->customerName; }
    public function getCustomerReferenceId(): string { return $this->customerReferenceId; }
    public function getDeviceNickname(): string { return $this->deviceNickname; }
    public function getThirdPartyFees(): float { return $this->thirdPartyFees; }
    public function getDepositId(): string { return $this->depositId; }
    public function getDepositDate(): string { return $this->depositDate; }
    public function getDepositDetails(): string { return $this->depositDetails; }
    public function getFeePercentageRate(): float { return $this->feePercentageRate; }
    public function getFeeFixedRate(): float { return $this->feeFixedRate; }
    public function getRefundReason(): string { return $this->refundReason; }
    public function getDiscountName(): string { return $this->discountName; }
    public function getTransactionStatus(): string { return $this->transactionStatus; }
    public function getOrderReferenceId(): string { return $this->orderReferenceId; }
    public function getFulfillmentNote(): string { return $this->fulfillmentNote; }
    public function getFreeProcessingApplied(): float { return $this->freeProcessingApplied; }
    public function getRawJson(): string { return $this->rawJson; }
    public function getSquareLocationId(): int { return $this->squareLocationId; }

    /**
     * Key map: legacy snake_case / alternate key => camelCase property name.
     *
     * @return array<string, string>
     */
    private static function keyMap(): array
    {
        return [
            'Date'                 => 'date',
            'date'                 => 'date',
            'Time'                 => 'time',
            'time'                 => 'time',
            'Timezone'             => 'timezone',
            'timezone'             => 'timezone',
            'gross_sales'          => 'grossSales',
            'discounts'            => 'discounts',
            'service_charges'      => 'serviceCharges',
            'gift_card_sales'      => 'giftCardSales',
            'net_sales'            => 'netSales',
            'tax'                  => 'tax',
            'tip'                  => 'tip',
            'partial_refunds'      => 'partialRefunds',
            'total_collected'      => 'totalCollected',
            'source_transaction_id' => 'sourceTransactionId',
            'transaction_id'       => 'sourceTransactionId',
            'payment_id'           => 'paymentId',
            'card_brand'           => 'cardBrand',
            'PAN_suffix'           => 'panSuffix',
            'pan_suffix'           => 'panSuffix',
            'device_name'          => 'deviceName',
            'staff_name'           => 'staffName',
            'staff_id'             => 'staffId',
            'event_type'           => 'eventType',
            'Dining_option'        => 'diningOption',
            'dining_option'        => 'diningOption',
            'Customer_id'          => 'customerId',
            'customer_id'          => 'customerId',
            'square_customer_id'   => 'customerId',
            'customer_name'        => 'customerName',
            'customer_reference_id' => 'customerReferenceId',
            'device_nickname'      => 'deviceNickname',
            'third_party_fees'     => 'thirdPartyFees',
            'deposit_id'           => 'depositId',
            'deposit_date'         => 'depositDate',
            'deposit_details'      => 'depositDetails',
            'fee_percentage_rate'  => 'feePercentageRate',
            'fee_fixed_rate'       => 'feeFixedRate',
            'refund_reason'        => 'refundReason',
            'discount_name'        => 'discountName',
            'transaction_status'   => 'transactionStatus',
            'order_reference_id'   => 'orderReferenceId',
            'fulfillment_note'     => 'fulfillmentNote',
            'free_processing_applied' => 'freeProcessingApplied',
            'square_location_id'   => 'squareLocationId',
            'raw_json'             => 'rawJson',
            'rawJson'              => 'rawJson',
            'source'               => 'source',
        ];
    }
}
