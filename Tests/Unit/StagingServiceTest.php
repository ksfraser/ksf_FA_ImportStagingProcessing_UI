<?php
declare(strict_types=1);

namespace Tests\Unit;

use Ksfraser\ImportStaging\StagingService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for StagingService.
 *
 * @BABOK Related: FR-ISU-010
 * @since 2.0.0
 */
class StagingServiceTest extends TestCase
{
    /** @test */
    public function stageTransactionReturnsErrorWhenNoTransactionId(): void
    {
        $service = new StagingService();
        $result = $service->stageTransaction([]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing source_transaction_id', $result['error']);
    }

    /** @test */
    public function stageTransactionReturnsErrorWhenEmptyTransactionId(): void
    {
        $service = new StagingService();
        $result = $service->stageTransaction(['transaction_id' => '', 'source_transaction_id' => '']);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing source_transaction_id', $result['error']);
    }

    /** @test */
    public function stageItemReturnsErrorWhenNoTransactionId(): void
    {
        $service = new StagingService();
        $result = $service->stageItem([]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing transaction_id', $result['error']);
    }

    /** @test */
    public function stagePaymentReturnsErrorWhenNoTransactionId(): void
    {
        $service = new StagingService();
        $result = $service->stagePayment([]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing transaction_id', $result['error']);
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
        $this->assertStringContainsString('FA not available', $result['error']);
    }
}
