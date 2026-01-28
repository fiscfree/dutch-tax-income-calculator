<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Tests\Unit;

use DutchTaxCalculator\Config\JsonTaxConfigProvider;
use DutchTaxCalculator\Exception\InvalidYearException;
use DutchTaxCalculator\Tests\TestCase;
use InvalidArgumentException;

final class JsonTaxConfigProviderTest extends TestCase
{
    private JsonTaxConfigProvider $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new JsonTaxConfigProvider(__DIR__ . '/../../data/data.json');
    }

    public function testGetSupportedYears(): void
    {
        $years = $this->config->getSupportedYears();

        $this->assertContains(2015, $years);
        $this->assertContains(2026, $years);
        $this->assertCount(12, $years);
    }

    public function testGetCurrentYear(): void
    {
        $this->assertEquals(2026, $this->config->getCurrentYear());
    }

    public function testGetWorkingWeeks(): void
    {
        $this->assertEquals(52, $this->config->getWorkingWeeks());
    }

    public function testGetWorkingDays(): void
    {
        $this->assertEquals(255, $this->config->getWorkingDays());
    }

    public function testGetDefaultWorkingHours(): void
    {
        $this->assertEquals(40, $this->config->getDefaultWorkingHours());
    }

    public function testIsYearSupported(): void
    {
        $this->assertTrue($this->config->isYearSupported(2025));
        $this->assertTrue($this->config->isYearSupported(2015));
        $this->assertFalse($this->config->isYearSupported(2000));
        $this->assertFalse($this->config->isYearSupported(2030));
    }

    /**
     * @throws InvalidYearException
     */
    public function testGetTaxRatesForYear(): void
    {
        $rates = $this->config->getTaxRatesForYear(2025);

        $this->assertNotEmpty($rates->payrollTaxBrackets);
        $this->assertNotEmpty($rates->socialPercentBrackets);
        $this->assertNotEmpty($rates->generalCreditBrackets);
        $this->assertNotEmpty($rates->labourCreditBrackets);
        $this->assertNotEmpty($rates->elderCreditBrackets);
        $this->assertGreaterThan(0, $rates->rulingThresholdNormal);
        $this->assertGreaterThan(0, $rates->rulingThresholdYoung);
        $this->assertGreaterThan(0, $rates->rulingMaxSalary);
        $this->assertGreaterThan(0, $rates->lowWageThreshold);
    }

    public function testGetTaxRatesForUnsupportedYear(): void
    {
        $this->expectException(InvalidYearException::class);
        $this->expectExceptionMessage('Tax year 2000 is not supported');

        $this->config->getTaxRatesForYear(2000);
    }

    /**
     * @throws InvalidYearException
     */
    public function testRatesAreCached(): void
    {
        $rates1 = $this->config->getTaxRatesForYear(2025);
        $rates2 = $this->config->getTaxRatesForYear(2025);

        // Same instance should be returned
        $this->assertSame($rates1, $rates2);
    }

    /**
     * @throws InvalidYearException
     */
    public function testPayrollTaxBracketsStructure(): void
    {
        $rates = $this->config->getTaxRatesForYear(2025);

        foreach ($rates->payrollTaxBrackets as $bracket) {
            $this->assertGreaterThanOrEqual(0, $bracket->min);
        }
    }

    public function testFileNotFoundThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax data file not found');

        new JsonTaxConfigProvider('/nonexistent/path/data.json');
    }

    public function testToArray(): void
    {
        $array = $this->config->toArray();

        $this->assertArrayHasKey('currentYear', $array);
        $this->assertArrayHasKey('years', $array);
        $this->assertArrayHasKey('payrollTax', $array);
        $this->assertArrayHasKey('socialPercent', $array);
    }
}
