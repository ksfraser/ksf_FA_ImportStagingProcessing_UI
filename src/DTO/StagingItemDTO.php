<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\DTO;

/**
 * DTO for staging a single line-item row.
 *
 * Maps 1:1 to the 0_ksf_import_square_items table columns.
 * Multiple items can exist per transaction (linked by sourceTransactionId).
 * Items are additive — no deduplication is performed.
 *
 * Required field: sourceTransactionId (links to parent transaction).
 *
 * @since 2.1.0
 */
class StagingItemDTO
{
    /** @var string Transaction date (YYYY-MM-DD) */
    private $date = '';

    /** @var string Transaction time (HH:MM:SS) */
    private $time = '';

    /** @var string Timezone string */
    private $timezone = '';

    /** @var string Item category */
    private $category = '';

    /** @var string Item name */
    private $item = '';

    /** @var string Price point name */
    private $pricePointName = '';

    /** @var string FA stock_id mapping */
    private $stockId = '';

    /** @var string Modifiers applied (comma-separated) */
    private $modifiersApplied = '';

    /** @var int Quantity */
    private $quantity = 0;

    /** @var float Gross sales amount */
    private $grossSales = 0.0;

    /** @var float Discount amount */
    private $discounts = 0.0;

    /** @var float Net sales amount */
    private $netSales = 0.0;

    /** @var float Tax amount */
    private $tax = 0.0;

    /** @var string Parent transaction source ID */
    private $sourceTransactionId = '';

    /** @var string Payment ID */
    private $paymentId = '';

    /** @var string Device name */
    private $deviceName = '';

    /** @var string Notes */
    private $notes = '';

    /** @var string Additional details */
    private $details = '';

    /** @var string Event type */
    private $eventType = '';

    /** @var string Location */
    private $location = '';

    /** @var string Dining option */
    private $diningOption = '';

    /** @var int Customer ID */
    private $customerId = 0;

    /** @var string Customer name */
    private $customerName = '';

    /** @var string Customer reference ID */
    private $customerReferenceId = '';

    /** @var string Unit of measure */
    private $unit = '';

    /** @var float Count (quantity variant) */
    private $count = 0.0;

    /** @var string Itemization type */
    private $itemizationType = '';

    /** @var string Fulfillment note */
    private $fulfillmentNote = '';

    /** @var string Raw JSON */
    private $rawJson = '';

    // -- Factory ------------------------------------------------------------

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
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'Date'                 => $this->date,
            'Time'                 => $this->time,
            'Timezone'             => $this->timezone,
            'Category'             => $this->category,
            'Item'                 => $this->item,
            'Price_Point_Name'     => $this->pricePointName,
            'stock_id'             => $this->stockId,
            'modifiers_applied'    => $this->modifiersApplied,
            'quantity'             => $this->quantity,
            'gross_sales'          => $this->grossSales,
            'discounts'            => $this->discounts,
            'net_sales'            => $this->netSales,
            'tax'                  => $this->tax,
            'transaction_id'       => $this->sourceTransactionId,
            'payment_id'           => $this->paymentId,
            'device_name'          => $this->deviceName,
            'notes'                => $this->notes,
            'details'              => $this->details,
            'event_type'           => $this->eventType,
            'location'             => $this->location,
            'dining_option'        => $this->diningOption,
            'Customer_id'          => $this->customerId,
            'customer_name'        => $this->customerName,
            'customer_reference_id' => $this->customerReferenceId,
            'unit'                 => $this->unit,
            'count'                => $this->count,
            'itemization_type'     => $this->itemizationType,
            'fulfillment_note'     => $this->fulfillmentNote,
            'raw_json'             => $this->rawJson,
        ];
    }

    // -- Validation ---------------------------------------------------------

    /**
     * @return StagingError|null
     */
    public function validate(): ?StagingError
    {
        if ($this->sourceTransactionId === '') {
            return new StagingError(
                StagingError::MISSING_REQUIRED,
                'transaction_id (parent link) is required',
                'sourceTransactionId'
            );
        }

        return null;
    }

    // -- Getters ------------------------------------------------------------

    public function getDate(): string { return $this->date; }
    public function getTime(): string { return $this->time; }
    public function getTimezone(): string { return $this->timezone; }
    public function getCategory(): string { return $this->category; }
    public function getItem(): string { return $this->item; }
    public function getPricePointName(): string { return $this->pricePointName; }
    public function getStockId(): string { return $this->stockId; }
    public function getModifiersApplied(): string { return $this->modifiersApplied; }
    public function getQuantity(): int { return $this->quantity; }
    public function getGrossSales(): float { return $this->grossSales; }
    public function getDiscounts(): float { return $this->discounts; }
    public function getNetSales(): float { return $this->netSales; }
    public function getTax(): float { return $this->tax; }
    public function getSourceTransactionId(): string { return $this->sourceTransactionId; }
    public function getPaymentId(): string { return $this->paymentId; }
    public function getDeviceName(): string { return $this->deviceName; }
    public function getNotes(): string { return $this->notes; }
    public function getDetails(): string { return $this->details; }
    public function getEventType(): string { return $this->eventType; }
    public function getLocation(): string { return $this->location; }
    public function getDiningOption(): string { return $this->diningOption; }
    public function getCustomerId(): int { return $this->customerId; }
    public function getCustomerName(): string { return $this->customerName; }
    public function getCustomerReferenceId(): string { return $this->customerReferenceId; }
    public function getUnit(): string { return $this->unit; }
    public function getCount(): float { return $this->count; }
    public function getItemizationType(): string { return $this->itemizationType; }
    public function getFulfillmentNote(): string { return $this->fulfillmentNote; }
    public function getRawJson(): string { return $this->rawJson; }

    /**
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
            'Category'             => 'category',
            'category'             => 'category',
            'Item'                 => 'item',
            'item'                 => 'item',
            'name'                 => 'item',
            'Price_Point_Name'     => 'pricePointName',
            'price_point_name'     => 'pricePointName',
            'stock_id'             => 'stockId',
            'sku'                  => 'stockId',
            'modifiers_applied'    => 'modifiersApplied',
            'quantity'             => 'quantity',
            'gross_sales'          => 'grossSales',
            'discounts'            => 'discounts',
            'net_sales'            => 'netSales',
            'tax'                  => 'tax',
            'transaction_id'       => 'sourceTransactionId',
            'source_transaction_id' => 'sourceTransactionId',
            'payment_id'           => 'paymentId',
            'device_name'          => 'deviceName',
            'event_type'           => 'eventType',
            'location'             => 'location',
            'dining_option'        => 'diningOption',
            'Dining_option'        => 'diningOption',
            'Customer_id'          => 'customerId',
            'customer_id'          => 'customerId',
            'customer_name'        => 'customerName',
            'customer_reference_id' => 'customerReferenceId',
            'unit'                 => 'unit',
            'count'                => 'count',
            'itemization_type'     => 'itemizationType',
            'fulfillment_note'     => 'fulfillmentNote',
            'raw_json'             => 'rawJson',
        ];
    }
}
