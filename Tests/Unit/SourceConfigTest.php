<?php
declare(strict_types=1);

namespace Tests\Unit;

use Ksfraser\ImportStaging\SourceConfig;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SourceConfig value object.
 *
 * @since 2.0.0
 */
class SourceConfigTest extends TestCase
{
    /** @test */
    public function constructWithRequiredFields(): void
    {
        $config = new SourceConfig('square', 1060, 5);

        $this->assertSame('square', $config->getSource());
        $this->assertSame(1060, $config->getGlAccount());
        $this->assertSame(5, $config->getBankAccount());
    }

    /** @test */
    public function constructWithDefaults(): void
    {
        $config = new SourceConfig();

        $this->assertSame('', $config->getSource());
        $this->assertSame(0, $config->getGlAccount());
        $this->assertSame(0, $config->getBankAccount());
        $this->assertSame(0, $config->getCashGl());
        $this->assertSame(0, $config->getXferToGl());
        $this->assertSame(0, $config->getXferToBank());
        $this->assertSame(0, $config->getCashBank());
        $this->assertSame(0, $config->getDefaultPayCard());
        $this->assertSame(0, $config->getDefaultPayCash());
        $this->assertFalse($config->isUseCardAsBranch());
        $this->assertFalse($config->isAllowSkuChange());
    }

    /** @test */
    public function fromArrayWithAllFields(): void
    {
        $data = [
            'source'            => 'woocommerce',
            'gl_account'        => 1061,
            'cash_gl'           => 1062,
            'xfer_to_gl'        => 1063,
            'bank_account'      => 10,
            'xfer_to_bank'      => 11,
            'cash_bank'         => 12,
            'default_pay_card'  => 3,
            'default_pay_cash'  => 4,
            'useCardAsBranch'   => true,
            'allowSkuChange'    => true,
        ];

        $config = SourceConfig::fromArray($data);

        $this->assertSame('woocommerce', $config->getSource());
        $this->assertSame(1061, $config->getGlAccount());
        $this->assertSame(1062, $config->getCashGl());
        $this->assertSame(1063, $config->getXferToGl());
        $this->assertSame(10, $config->getBankAccount());
        $this->assertSame(11, $config->getXferToBank());
        $this->assertSame(12, $config->getCashBank());
        $this->assertSame(3, $config->getDefaultPayCard());
        $this->assertSame(4, $config->getDefaultPayCash());
        $this->assertTrue($config->isUseCardAsBranch());
        $this->assertTrue($config->isAllowSkuChange());
    }

    /** @test */
    public function fromArrayWithPartialFields(): void
    {
        $data = [
            'source'     => 'square',
            'gl_account' => 1060,
        ];

        $config = SourceConfig::fromArray($data);

        $this->assertSame('square', $config->getSource());
        $this->assertSame(1060, $config->getGlAccount());
        $this->assertSame(0, $config->getBankAccount());
        $this->assertSame(0, $config->getCashGl());
    }

    /** @test */
    public function fromArrayWithEmptyArray(): void
    {
        $config = SourceConfig::fromArray([]);

        $this->assertSame('', $config->getSource());
        $this->assertSame(0, $config->getGlAccount());
    }

    /** @test */
    public function toArrayRoundTrip(): void
    {
        $data = [
            'source'            => 'square',
            'gl_account'        => 1060,
            'cash_gl'           => 1062,
            'xfer_to_gl'        => 1063,
            'bank_account'      => 5,
            'xfer_to_bank'      => 6,
            'cash_bank'         => 7,
            'default_pay_card'  => 2,
            'default_pay_cash'  => 3,
            'useCardAsBranch'   => true,
            'allowSkuChange'    => false,
        ];

        $config = SourceConfig::fromArray($data);
        $result = $config->toArray();

        $this->assertSame($data, $result);
    }

    /** @test */
    public function fluentWithersReturnNewInstances(): void
    {
        $original = new SourceConfig('square', 1060, 5);
        $modified = $original->withGlAccount(2000);

        $this->assertSame(1060, $original->getGlAccount(), 'Original unchanged');
        $this->assertSame(2000, $modified->getGlAccount(), 'Modified has new value');
        $this->assertSame('square', $modified->getSource(), 'Source preserved');
    }

    /** @test */
    public function witherFluentChain(): void
    {
        $config = (new SourceConfig('square'))
            ->withGlAccount(1060)
            ->withCashGl(1062)
            ->withBankAccount(5)
            ->withXferToBank(6)
            ->withUseCardAsBranch(true);

        $this->assertSame(1060, $config->getGlAccount());
        $this->assertSame(1062, $config->getCashGl());
        $this->assertSame(5, $config->getBankAccount());
        $this->assertSame(6, $config->getXferToBank());
        $this->assertTrue($config->isUseCardAsBranch());
    }

    /** @test */
    public function getGlForAmountWithPositiveAmountReturnsMainGl(): void
    {
        $config = new SourceConfig('square', 1060, 5);
        $config = $config->withCashGl(1062);

        $this->assertSame(1060, $config->getGlForAmount(100.50));
    }

    /** @test */
    public function getGlForAmountWithZeroAmountReturnsCashGl(): void
    {
        $config = (new SourceConfig('square', 1060, 5))
            ->withCashGl(1062);

        $this->assertSame(1062, $config->getGlForAmount(0));
    }

    /** @test */
    public function getGlForAmountWithNegativeAmountReturnsCashGl(): void
    {
        $config = (new SourceConfig('square', 1060, 5))
            ->withCashGl(1062);

        $this->assertSame(1062, $config->getGlForAmount(-50.00));
    }

    /** @test */
    public function getGlForAmountFallsBackToMainGlWhenCashGlNotSet(): void
    {
        $config = new SourceConfig('square', 1060, 5);

        $this->assertSame(1060, $config->getGlForAmount(0));
    }

    /** @test */
    public function getGlForAmountWithZeroMainGlReturnsZero(): void
    {
        $config = new SourceConfig('square', 0, 0);

        $this->assertSame(0, $config->getGlForAmount(100));
    }
}
