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
 * @since 2.1.0 DTO-based tests added
 */
class StagingServiceTest extends TestCase
{
    // =========================================================================
    // Legacy array-based API tests
    // =========================================================================

    /** @test */
    public function stageTransactionReturnsErrorWhenNoTransactionId(): void
    {
        $service = new StagingService();
        $result = $service->stageTransaction([]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('source_transaction_id', $result['error']);
    }

    /** @test */
    public function stageTransactionReturnsErrorWhenEmptyTransactionId(): void
    {
        $service = new StagingService();
        $result = $service->stageTransaction(['transaction_id' => '', 'source_transaction_id' => '']);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('source_transaction_id', $result['error']);
    }

    /** @test */
    public function stageItemReturnsErrorWhenNoTransactionId(): void
    {
        $service = new StagingService();
        $result = $service->stageItem([]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('transaction_id', $result['error']);
    }

    /** @test */
    public function stagePaymentReturnsErrorWhenNoPaymentId(): void
    {
        $service = new StagingService();
        $result = $service->stagePayment([]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('square_payment_id', $result['error']);
    }

    /** @test */
    public function updateStatusReturnsErrorWhenNoId(): void
    {
        $service = new StagingService();
        $result = $service->updateStatus(0, 'imported');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing id', $result['error']);
    }

    /** @test */
    public function updateStatusReturnsErrorWhenNoStatus(): void
    {
        $service = new StagingService();
        $result = $service->updateStatus(1, '');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing id or status', $result['error']);
    }

    /** @test */
    public function transactionExistsReturnsFalseWhenNoFa(): void
    {
        $service = new StagingService();
        $this->assertFalse($service->transactionExists('txn_123'));
    }

    /** @test */
    public function stageTransactionReturnsErrorWhenNoFa(): void
    {
        $service = new StagingService();
        $result = $service->stageTransaction([
            'source_transaction_id' => 'txn_123',
            'total_amount' => 100.00,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('FA database layer', $result['error']);
    }

    // =========================================================================
    // DTO-based API tests
    // =========================================================================

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
}
