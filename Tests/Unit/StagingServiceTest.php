<?php
declare(strict_types=1);

namespace Tests\Unit;

use Ksfraser\ImportStaging\StagingService;
use Ksfraser\ImportStaging\DTO\StagingTransactionDTO;
use Ksfraser\ImportStaging\DTO\StagingItemDTO;
use Ksfraser\ImportStaging\DTO\StagingPaymentDTO;
use Ksfraser\ImportStaging\DTO\StagingResult;
use Ksfraser\ImportStaging\DTO\StagingError;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for StagingService.
 *
 * @BABOK Related: FR-ISU-010
 * @since 2.0.0
 * @since 2.1.0 DTO-only API
 */
class StagingServiceTest extends TestCase
{
    /** @test */
    public function stageTransactionDTOFailsValidationWithoutTransactionId(): void
    {
        $service = new StagingService();
        $dto = new StagingTransactionDTO();
        $result = $service->stageTransactionDTO($dto);

        $this->assertFalse($result->isOk());
        $this->assertEquals(StagingResult::STATUS_ERROR, $result->getStatus());
        $this->assertEquals(StagingError::MISSING_REQUIRED, $result->getError()->getCode());
        $this->assertEquals('sourceTransactionId', $result->getError()->getField());
    }

    /** @test */
    public function stageTransactionDTOFailsWhenFaUnavailable(): void
    {
        $service = new StagingService();
        $dto = StagingTransactionDTO::fromArray([
            'source_transaction_id' => 'txn_abc',
            'total_collected' => 50.00,
        ]);
        $result = $service->stageTransactionDTO($dto);

        $this->assertFalse($result->isOk());
        $this->assertEquals(StagingError::FA_NOT_AVAILABLE, $result->getError()->getCode());
    }

    /** @test */
    public function stageTransactionDTOFailsValidationWithBadDateFormat(): void
    {
        $service = new StagingService();
        $dto = StagingTransactionDTO::fromArray([
            'source_transaction_id' => 'txn_abc',
            'date' => 'not-a-date',
        ]);
        $result = $service->stageTransactionDTO($dto);

        $this->assertFalse($result->isOk());
        $this->assertEquals(StagingError::INVALID_FORMAT, $result->getError()->getCode());
        $this->assertEquals('date', $result->getError()->getField());
    }

    /** @test */
    public function stageItemDTOFailsValidationWithoutTransactionId(): void
    {
        $service = new StagingService();
        $dto = new StagingItemDTO();
        $result = $service->stageItemDTO($dto);

        $this->assertFalse($result->isOk());
        $this->assertEquals(StagingError::MISSING_REQUIRED, $result->getError()->getCode());
    }

    /** @test */
    public function stageItemDTOFailsWhenFaUnavailable(): void
    {
        $service = new StagingService();
        $dto = StagingItemDTO::fromArray([
            'source_transaction_id' => 'txn_abc',
            'item' => 'Widget',
            'quantity' => 2,
        ]);
        $result = $service->stageItemDTO($dto);

        $this->assertFalse($result->isOk());
        $this->assertEquals(StagingError::FA_NOT_AVAILABLE, $result->getError()->getCode());
    }

    /** @test */
    public function stagePaymentDTOFailsValidationWithoutPaymentId(): void
    {
        $service = new StagingService();
        $dto = new StagingPaymentDTO();
        $result = $service->stagePaymentDTO($dto);

        $this->assertFalse($result->isOk());
        $this->assertEquals(StagingError::MISSING_REQUIRED, $result->getError()->getCode());
        $this->assertEquals('paymentId', $result->getError()->getField());
    }

    /** @test */
    public function stagePaymentDTOFailsWhenFaUnavailable(): void
    {
        $service = new StagingService();
        $dto = StagingPaymentDTO::fromArray([
            'square_payment_id' => 'pay_123',
            'total_collected' => 25.00,
        ]);
        $result = $service->stagePaymentDTO($dto);

        $this->assertFalse($result->isOk());
        $this->assertEquals(StagingError::FA_NOT_AVAILABLE, $result->getError()->getCode());
    }

    /** @test */
    public function updateStatusFailsWithoutId(): void
    {
        $service = new StagingService();
        $result = $service->updateStatus(0, 'imported');

        $this->assertFalse($result->isOk());
        $this->assertStringContainsString('Missing', $result->getError()->getMessage());
    }

    /** @test */
    public function updateStatusFailsWithoutStatus(): void
    {
        $service = new StagingService();
        $result = $service->updateStatus(1, '');

        $this->assertFalse($result->isOk());
        $this->assertStringContainsString('Missing', $result->getError()->getMessage());
    }

    /** @test */
    public function updateStatusFailsWhenFaUnavailable(): void
    {
        $service = new StagingService();
        $result = $service->updateStatus(1, 'imported');

        $this->assertFalse($result->isOk());
        $this->assertEquals(StagingError::FA_NOT_AVAILABLE, $result->getError()->getCode());
    }
}
