<?php
declare(strict_types=1);

namespace Tests\Unit;

use Ksfraser\ImportStaging\SourceConfig;
use PHPUnit\Framework\TestCase;

/**
 * Tests for GL mismatch detection in staged transaction processing.
 *
 * @BABOK Related: FR-ISU-012
 * @since 2.0.0
 */
class GlMismatchDetectionTest extends TestCase
{
    /** @test */
    public function sourceConfigReturnsGlAccount(): void
    {
        $config = SourceConfig::fromArray([
            'source' => 'square',
            'gl_account' => 1060,
            'cash_gl' => 1070,
        ]);

        $this->assertSame(1060, $config->getGlAccount());
        $this->assertSame(1070, $config->getCashGl());
    }

    /** @test */
    public function sourceConfigGlForAmountReturnsCardGl(): void
    {
        $config = SourceConfig::fromArray([
            'source' => 'square',
            'gl_account' => 1060,
            'cash_gl' => 1070,
        ]);

        $this->assertSame(1060, $config->getGlForAmount(100.0));
    }

    /** @test */
    public function sourceConfigGlForAmountReturnsCashGl(): void
    {
        $config = SourceConfig::fromArray([
            'source' => 'square',
            'gl_account' => 1060,
            'cash_gl' => 1070,
        ]);

        $this->assertSame(1070, $config->getGlForAmount(-50.0));
    }

    /** @test */
    public function sourceConfigToArrayRoundTrip(): void
    {
        $config = SourceConfig::fromArray([
            'source' => 'square',
            'gl_account' => 1060,
            'cash_gl' => 1070,
            'xfer_to_gl' => 1080,
            'bank_account' => 5,
            'xfer_to_bank' => 6,
            'cash_bank' => 7,
        ]);

        $arr = $config->toArray();
        $restored = SourceConfig::fromArray($arr);

        $this->assertSame('square', $restored->getSource());
        $this->assertSame(1060, $restored->getGlAccount());
        $this->assertSame(1070, $restored->getCashGl());
        $this->assertSame(1080, $restored->getXferToGl());
        $this->assertSame(5, $restored->getBankAccount());
        $this->assertSame(6, $restored->getXferToBank());
        $this->assertSame(7, $restored->getCashBank());
    }
}
