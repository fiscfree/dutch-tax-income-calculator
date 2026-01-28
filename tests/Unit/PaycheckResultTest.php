<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Tests\Unit;

use DutchTaxCalculator\DTO\PaycheckResult;
use DutchTaxCalculator\Tests\TestCase;

final class PaycheckResultTest extends TestCase
{
    private PaycheckResult $result;

    protected function setUp(): void
    {
        parent::setUp();

        $this->result = new PaycheckResult(
            grossYear: 60000.0,
            grossAllowance: 4444.44,
            taxFreeYear: 0.0,
            taxableYear: 55555.56,
            payrollTax: -5000.0,
            socialTax: -8000.0,
            labourCredit: 3000.0,
            generalCredit: 2000.0,
            netYear: 47555.56,
            netAllowance: 3518.93,
            workingWeeks: 52,
            workingDays: 255,
            hoursPerWeek: 40.0,
        );
    }

    public function testComputedGrossAmounts(): void
    {
        $this->assertEquals(5000.0, $this->result->grossMonth);
        $this->assertEquals(1153.85, $this->result->grossWeek);
        $this->assertEquals(235.29, $this->result->grossDay);
        $this->assertEquals(28.85, $this->result->grossHour);
    }

    public function testComputedNetAmounts(): void
    {
        $this->assertEquals(3962.96, $this->result->netMonth);
        $this->assertEquals(914.53, $this->result->netWeek);
        $this->assertEquals(186.49, $this->result->netDay);
        $this->assertEquals(22.86, $this->result->netHour);
    }

    public function testComputedTaxAmounts(): void
    {
        $this->assertEquals(-13000.0, $this->result->taxWithoutCredit);
        $this->assertEquals(-1083.33, $this->result->taxWithoutCreditMonth);
        $this->assertEquals(5000.0, $this->result->taxCredit);
        $this->assertEquals(416.67, $this->result->taxCreditMonth);
        $this->assertEquals(-8000.0, $this->result->incomeTax);
        $this->assertEquals(-666.67, $this->result->incomeTaxMonth);
    }

    public function testComputedMonthlyTaxBreakdown(): void
    {
        $this->assertEquals(-416.67, $this->result->payrollTaxMonth);
        $this->assertEquals(-666.67, $this->result->socialTaxMonth);
        $this->assertEquals(250.0, $this->result->labourCreditMonth);
        $this->assertEquals(166.67, $this->result->generalCreditMonth);
    }

    public function testTaxFreePercent(): void
    {
        $this->assertEquals(0.0, $this->result->taxFreePercent);

        // Create result with tax-free amount
        $resultWithRuling = new PaycheckResult(
            grossYear: 80000.0,
            grossAllowance: 0.0,
            taxFreeYear: 10000.0,
            taxableYear: 70000.0,
            payrollTax: -10000.0,
            socialTax: -5000.0,
            labourCredit: 2000.0,
            generalCredit: 1000.0,
            netYear: 58000.0,
            netAllowance: 0.0,
        );

        $this->assertEquals(12.5, $resultWithRuling->taxFreePercent);
    }

    public function testEffectiveTaxRate(): void
    {
        $this->assertEquals(13.33, $this->result->effectiveTaxRate);
    }

    public function testToArray(): void
    {
        $array = $this->result->toArray();

        $this->assertCount(30, $array);

        // Check key properties exist
        $this->assertArrayHasKey('grossYear', $array);
        $this->assertArrayHasKey('grossMonth', $array);
        $this->assertArrayHasKey('netYear', $array);
        $this->assertArrayHasKey('netMonth', $array);
        $this->assertArrayHasKey('incomeTax', $array);
        $this->assertArrayHasKey('effectiveTaxRate', $array);

        // Check values
        $this->assertEquals(60000.0, $array['grossYear']);
        $this->assertEquals(5000.0, $array['grossMonth']);
        $this->assertEquals(47555.56, $array['netYear']);
    }

    public function testZeroGrossYear(): void
    {
        $result = new PaycheckResult(
            grossYear: 0.0,
            grossAllowance: 0.0,
            taxFreeYear: 0.0,
            taxableYear: 0.0,
            payrollTax: 0.0,
            socialTax: 0.0,
            labourCredit: 0.0,
            generalCredit: 0.0,
            netYear: 0.0,
            netAllowance: 0.0,
        );

        $this->assertEquals(0.0, $result->taxFreePercent);
        $this->assertEquals(0.0, $result->effectiveTaxRate);
    }
}
