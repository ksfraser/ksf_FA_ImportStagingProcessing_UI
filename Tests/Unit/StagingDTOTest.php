<?php
declare(strict_types=1);

namespace Tests\Unit;

use Ksfraser\ImportStaging\DTO\StagingTransactionDTO;
use Ksfraser\ImportStaging\DTO\StagingItemDTO;
use Ksfraser\ImportStaging\DTO\StagingPaymentDTO;
use Ksfraser\ImportStaging\DTO\StagingResult;
use Ksfraser\ImportStaging\DTO\StagingError;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for staging DTOs.
 *
 * @BABOK Related: FR-ISU-010
 * @since 2.1.0
 */
class StagingDTOTest extends TestCase
{
    // =========================================================================
    // StagingError tests
    // =========================================================================

    /** @test */
    public function errorToArrayRoundTrip(): void
    {
        $error = new StagingError(
            StagingError::MISSING_REQUIRED,
            'field is required',
            'fieldName',
            ['extra' => 'context']
        );

        $arr = $error->toArray();
        $restored = StagingError::fromArray($arr);

        $this->assertEquals(StagingError::MISSING_REQUIRED, $restored->getCode());
        $this->assertEquals('field is required', $restored->getMessage());
        $this->assertEquals('fieldName', $restored->getField());
        $this->assertEquals(['extra' => 'context'], $restored->getContext());
    }

    // =========================================================================
    // StagingResult tests
    // =========================================================================

    /** @test */
    public function resultInsertedFactory(): void
    {
        $result = StagingResult::inserted(42);

        $this->assertEquals(StagingResult::STATUS_INSERTED, $result->getStatus());
        $this->assertEquals(42, $result->getId());
        $this->assertNull($result->getError());
        $this->assertTrue($result->isOk());
    }

    /** @test */
    public function resultUpdatedFactory(): void
    {
        $result = StagingResult::updated(99);

        $this->assertEquals(StagingResult::STATUS_UPDATED, $result->getStatus());
        $this->assertEquals(99, $result->getId());
        $this->assertTrue($result->isOk());
    }

    /** @test */
    public function resultDuplicateFactory(): void
    {
        $result = StagingResult::duplicate(7);

        $this->assertEquals(StagingResult::STATUS_DUPLICATE, $result->getStatus());
        $this->assertEquals(7, $result->getId());
        $this->assertTrue($result->isOk());
    }

    /** @test */
    public function resultErrorFactory(): void
    {
        $error = new StagingError(StagingError::DB_ERROR, 'boom');
        $result = StagingResult::error($error);

        $this->assertEquals(StagingResult::STATUS_ERROR, $result->getStatus());
        $this->assertEquals(0, $result->getId());
        $this->assertFalse($result->isOk());
        $this->assertSame($error, $result->getError());
    }

    /** @test */
    public function resultToArrayRoundTrip(): void
    {
        $error = new StagingError(StagingError::INVALID_FORMAT, 'bad date', 'date');
        $result = StagingResult::error($error);

        $arr = $result->toArray();
        $restored = StagingResult::fromArray($arr);

        $this->assertEquals(StagingResult::STATUS_ERROR, $restored->getStatus());
        $this->assertEquals(0, $restored->getId());
        $this->assertEquals(StagingError::INVALID_FORMAT, $restored->getError()->getCode());
    }

    /** @test */
    public function resultOkFactoryRoundTrip(): void
    {
        $result = StagingResult::inserted(123);
        $arr = $result->toArray();
        $restored = StagingResult::fromArray($arr);

        $this->assertEquals(StagingResult::STATUS_INSERTED, $restored->getStatus());
        $this->assertEquals(123, $restored->getId());
        $this->assertNull($restored->getError());
    }

    // =========================================================================
    // StagingTransactionDTO tests
    // =========================================================================

    /** @test */
    public function transactionDTOFromSnakeCaseKeys(): void
    {
        $dto = StagingTransactionDTO::fromArray([
            'transaction_id' => 'txn_001',
            'total_collected' => 125.50,
            'gross_sales' => 100.00,
            'tax' => 12.50,
            'source' => 'square',
            'Date' => '2026-01-15',
            'customer_name' => 'John Doe',
            'Customer_id' => 42,
        ]);

        $this->assertEquals('txn_001', $dto->getSourceTransactionId());
        $this->assertEquals(125.50, $dto->getTotalCollected());
        $this->assertEquals(100.00, $dto->getGrossSales());
        $this->assertEquals(12.50, $dto->getTax());
        $this->assertEquals('square', $dto->getSource());
        $this->assertEquals('2026-01-15', $dto->getDate());
        $this->assertEquals('John Doe', $dto->getCustomerName());
        $this->assertEquals(42, $dto->getCustomerId());
    }

    /** @test */
    public function transactionDTOFromCamelCaseKeys(): void
    {
        $dto = StagingTransactionDTO::fromArray([
            'sourceTransactionId' => 'txn_002',
            'totalCollected' => 75.00,
            'grossSales' => 60.00,
        ]);

        $this->assertEquals('txn_002', $dto->getSourceTransactionId());
        $this->assertEquals(75.00, $dto->getTotalCollected());
    }

    /** @test */
    public function transactionDTOToArrayReturnsSnakeCaseKeys(): void
    {
        $dto = StagingTransactionDTO::fromArray([
            'source_transaction_id' => 'txn_003',
            'total_collected' => 50.00,
        ]);

        $arr = $dto->toArray();

        $this->assertArrayHasKey('transaction_id', $arr);
        $this->assertArrayHasKey('total_collected', $arr);
        $this->assertEquals('txn_003', $arr['transaction_id']);
        $this->assertEquals(50.00, $arr['total_collected']);
    }

    /** @test */
    public function transactionDTORoundTrip(): void
    {
        $original = [
            'source' => 'woocommerce',
            'Date' => '2026-03-20',
            'Time' => '14:30:00',
            'gross_sales' => 200.00,
            'discounts' => 10.00,
            'net_sales' => 190.00,
            'tax' => 15.20,
            'total_collected' => 205.20,
            'card' => 205.20,
            'cash' => 0,
            'fees' => 6.15,
            'net_total' => 199.05,
            'transaction_id' => 'woo_999',
            'payment_id' => 'pay_888',
            'card_brand' => 'Visa',
            'customer_name' => 'Jane Smith',
        ];

        $dto = StagingTransactionDTO::fromArray($original);
        $exported = $dto->toArray();

        $this->assertEquals($original['transaction_id'], $exported['transaction_id']);
        $this->assertEquals($original['total_collected'], $exported['total_collected']);
        $this->assertEquals($original['card_brand'], $exported['card_brand']);
        $this->assertEquals($original['customer_name'], $exported['customer_name']);
    }

    /** @test */
    public function transactionDTOValidateSuccess(): void
    {
        $dto = StagingTransactionDTO::fromArray([
            'source_transaction_id' => 'txn_valid',
        ]);

        $this->assertNull($dto->validate());
    }

    /** @test */
    public function transactionDTOValidateFailsWithoutId(): void
    {
        $dto = new StagingTransactionDTO();
        $error = $dto->validate();

        $this->assertInstanceOf(StagingError::class, $error);
        $this->assertEquals(StagingError::MISSING_REQUIRED, $error->getCode());
    }

    /** @test */
    public function transactionDTOValidateFailsBadDate(): void
    {
        $dto = StagingTransactionDTO::fromArray([
            'source_transaction_id' => 'txn_1',
            'Date' => '15/01/2026',
        ]);

        $error = $dto->validate();

        $this->assertInstanceOf(StagingError::class, $error);
        $this->assertEquals(StagingError::INVALID_FORMAT, $error->getCode());
    }

    /** @test */
    public function transactionDTOAcceptsEmptyDateAsOptional(): void
    {
        $dto = StagingTransactionDTO::fromArray([
            'source_transaction_id' => 'txn_1',
            'Date' => '',
        ]);

        $this->assertNull($dto->validate());
    }

    /** @test */
    public function transactionDTOLegacyFieldMapAliases(): void
    {
        $dto = StagingTransactionDTO::fromArray([
            'transaction_id' => 'txn_alias',
            'square_customer_id' => 55,
            'dining_option' => 'dine_in',
            'Dining_option' => 'take_out',
            'pan_suffix' => 1234,
        ]);

        $this->assertEquals('txn_alias', $dto->getSourceTransactionId());
        $this->assertEquals(55, $dto->getCustomerId());
        $this->assertEquals('take_out', $dto->getDiningOption());
        $this->assertEquals(1234, $dto->getPanSuffix());
    }

    // =========================================================================
    // StagingItemDTO tests
    // =========================================================================

    /** @test */
    public function itemDTOFromSnakeCaseKeys(): void
    {
        $dto = StagingItemDTO::fromArray([
            'transaction_id' => 'txn_001',
            'Item' => 'Espresso',
            'quantity' => 2,
            'gross_sales' => 8.00,
            'net_sales' => 8.00,
            'tax' => 0.64,
        ]);

        $this->assertEquals('txn_001', $dto->getSourceTransactionId());
        $this->assertEquals('Espresso', $dto->getItem());
        $this->assertEquals(2, $dto->getQuantity());
        $this->assertEquals(8.00, $dto->getGrossSales());
    }

    /** @test */
    public function itemDTOToArrayReturnsSnakeCaseKeys(): void
    {
        $dto = StagingItemDTO::fromArray([
            'transaction_id' => 'txn_002',
            'Item' => 'Latte',
        ]);

        $arr = $dto->toArray();

        $this->assertArrayHasKey('transaction_id', $arr);
        $this->assertArrayHasKey('Item', $arr);
        $this->assertArrayHasKey('gross_sales', $arr);
    }

    /** @test */
    public function itemDTOValidateSuccess(): void
    {
        $dto = StagingItemDTO::fromArray([
            'source_transaction_id' => 'txn_ok',
        ]);

        $this->assertNull($dto->validate());
    }

    /** @test */
    public function itemDTOValidateFailsWithoutParentId(): void
    {
        $dto = new StagingItemDTO();
        $error = $dto->validate();

        $this->assertInstanceOf(StagingError::class, $error);
        $this->assertEquals(StagingError::MISSING_REQUIRED, $error->getCode());
    }

    /** @test */
    public function itemDTOLegacyKeyAliases(): void
    {
        $dto = StagingItemDTO::fromArray([
            'source_transaction_id' => 'txn_x',
            'name' => 'Cappuccino',
            'sku' => 'SKU-CC',
            'category' => 'Drinks',
            'dining_option' => 'dine_in',
        ]);

        $this->assertEquals('txn_x', $dto->getSourceTransactionId());
        $this->assertEquals('Cappuccino', $dto->getItem());
        $this->assertEquals('SKU-CC', $dto->getStockId());
        $this->assertEquals('Drinks', $dto->getCategory());
        $this->assertEquals('dine_in', $dto->getDiningOption());
    }

    // =========================================================================
    // StagingPaymentDTO tests
    // =========================================================================

    /** @test */
    public function paymentDTOFromKeys(): void
    {
        $dto = StagingPaymentDTO::fromArray([
            'square_payment_id' => 'pay_001',
            'total_collected' => 42.50,
            'trans_type' => 10,
            'trans_no' => '12345',
        ]);

        $this->assertEquals('pay_001', $dto->getPaymentId());
        $this->assertEquals(42.50, $dto->getTotalCollected());
        $this->assertEquals(10, $dto->getTransType());
        $this->assertEquals('12345', $dto->getTransNo());
    }

    /** @test */
    public function paymentDTOFromAlternativeKeys(): void
    {
        $dto = StagingPaymentDTO::fromArray([
            'payment_id' => 'pay_alt',
            'total_collected' => 99.99,
        ]);

        $this->assertEquals('pay_alt', $dto->getPaymentId());
        $this->assertEquals(99.99, $dto->getTotalCollected());
    }

    /** @test */
    public function paymentDTOToArray(): void
    {
        $dto = StagingPaymentDTO::fromArray([
            'square_payment_id' => 'pay_out',
            'total_collected' => 10.00,
        ]);

        $arr = $dto->toArray();

        $this->assertArrayHasKey('square_payment_id', $arr);
        $this->assertEquals('pay_out', $arr['square_payment_id']);
    }

    /** @test */
    public function paymentDTOValidateSuccess(): void
    {
        $dto = StagingPaymentDTO::fromArray([
            'square_payment_id' => 'pay_ok',
        ]);

        $this->assertNull($dto->validate());
    }

    /** @test */
    public function paymentDTOValidateFailsWithoutId(): void
    {
        $dto = new StagingPaymentDTO();
        $error = $dto->validate();

        $this->assertInstanceOf(StagingError::class, $error);
        $this->assertEquals(StagingError::MISSING_REQUIRED, $error->getCode());
        $this->assertEquals('paymentId', $error->getField());
    }
}
