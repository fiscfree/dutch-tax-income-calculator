<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Tests\Unit;

use DutchTaxCalculator\DTO\RulingOptions;
use DutchTaxCalculator\Enum\RulingType;
use DutchTaxCalculator\Tests\TestCase;

final class RulingOptionsTest extends TestCase
{
    public function testDisabledFactory(): void
    {
        $options = RulingOptions::disabled();

        $this->assertFalse($options->enabled);
        $this->assertEquals(RulingType::Normal, $options->type);
    }

    public function testEnabledFactory(): void
    {
        $options = RulingOptions::enabled();

        $this->assertTrue($options->enabled);
        $this->assertEquals(RulingType::Normal, $options->type);
    }

    public function testEnabledWithType(): void
    {
        $options = RulingOptions::enabled(RulingType::YoungMaster);

        $this->assertTrue($options->enabled);
        $this->assertEquals(RulingType::YoungMaster, $options->type);
    }

    public function testConstructorWithResearch(): void
    {
        $options = new RulingOptions(enabled: true, type: RulingType::Research);

        $this->assertTrue($options->enabled);
        $this->assertEquals(RulingType::Research, $options->type);
    }

    public function testConstructorDisabled(): void
    {
        $options = new RulingOptions(enabled: false);

        $this->assertFalse($options->enabled);
        $this->assertEquals(RulingType::Normal, $options->type);
    }
}
