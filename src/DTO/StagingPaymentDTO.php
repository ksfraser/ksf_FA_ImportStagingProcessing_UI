<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\DTO;

/**
 * DTO for staging a payment-match record.
 *
 * Maps 1:1 to the 0_ksf_import_square_payments table columns.
 * Deduplicates on paymentId (UNIQUE index).
 *
 * Required field: paymentId (non-empty).
 *
 * @since 2.1.0
 */
class StagingPaymentDTO
{
    /** @var string Source payment ID (unique key for dedup) */
    private $paymentId = '';

    /** @var float Total collected amount */
    private $totalCollected = 0.0;

    /** @var int FA transaction type (e.g., ST_SALESINVOICE) */
    private $transType = 0;

    /** @var string FA transaction number */
    private $transNo = '';

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
            'square_payment_id' => $this->paymentId,
            'total_collected'   => $this->totalCollected,
            'trans_type'        => $this->transType,
            'trans_no'          => $this->transNo,
        ];
    }

    // -- Validation ---------------------------------------------------------

    /**
     * @return StagingError|null
     */
    public function validate(): ?StagingError
    {
        if ($this->paymentId === '') {
            return new StagingError(
                StagingError::MISSING_REQUIRED,
                'square_payment_id is required',
                'paymentId'
            );
        }

        return null;
    }

    // -- Getters ------------------------------------------------------------

    public function getPaymentId(): string { return $this->paymentId; }
    public function getTotalCollected(): float { return $this->totalCollected; }
    public function getTransType(): int { return $this->transType; }
    public function getTransNo(): string { return $this->transNo; }

    /**
     * @return array<string, string>
     */
    private static function keyMap(): array
    {
        return [
            'square_payment_id' => 'paymentId',
            'payment_id'        => 'paymentId',
            'total_collected'   => 'totalCollected',
            'trans_type'        => 'transType',
            'trans_no'          => 'transNo',
        ];
    }
}
