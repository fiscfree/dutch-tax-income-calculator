<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Tests\Unit;

use DutchTaxCalculator\Enum\Period;
use DutchTaxCalculator\Tests\TestCase;

final class PeriodTest extends TestCase
{
    public function testToAnnualMultiplier(): void
    {
        $this->assertEquals(1.0, Period::Year->toAnnualMultiplier());
        $this->assertEquals(12.0, Period::Month->toAnnualMultiplier());
        $this->assertEquals(52.0, Period::Week->toAnnualMultiplier());
        $this->assertEquals(255.0, Period::Day->toAnnualMultiplier());
        $this->assertEquals(2080.0, Period::Hour->toAnnualMultiplier()); // 52 * 40
    }

    public function testToAnnualMultiplierWithCustomHours(): void
    {
        $this->assertEquals(1664.0, Period::Hour->toAnnualMultiplier(52, 255, 32.0)); // 52 * 32
    }

    public function testEnumValues(): void
    {
        $this->assertEquals('year', Period::Year->value);
        $this->assertEquals('month', Period::Month->value);
        $this->assertEquals('week', Period::Week->value);
        $this->assertEquals('day', Period::Day->value);
        $this->assertEquals('hour', Period::Hour->value);
    }
}
