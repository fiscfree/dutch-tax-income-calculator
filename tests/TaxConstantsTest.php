<?php

namespace DutchTaxCalculator\Tests;

use DutchTaxCalculator\TaxConstants;

class TaxConstantsTest extends TestCase
{
    private TaxConstants $constants;

    protected function setUp(): void
    {
        parent::setUp();
        $this->constants = new TaxConstants(__DIR__ . '/../data/data.json');
    }

    public function testConstantsHaveCurrentYear(): void
    {
        $this->assertIsInt($this->constants->getCurrentYear());
        $this->assertGreaterThan(2014, $this->constants->getCurrentYear());
    }

    public function testConstantsHaveYears(): void
    {
        $years = $this->constants->getYears();
        $this->assertIsArray($years);
        $this->assertNotEmpty($years);
        $this->assertContains(2025, $years);
    }

    public function testConstantsHaveRulingThreshold(): void
    {
        // Test a few years
        foreach ([2020, 2021, 2022, 2023, 2024, 2025] as $year) {
            $normal = $this->constants->getRulingThreshold($year, 'normal');
            $young = $this->constants->getRulingThreshold($year, 'young');
            $research = $this->constants->getRulingThreshold($year, 'research');
            
            $this->assertGreaterThan(0, $normal, "Normal ruling threshold should be > 0 for year {$year}");
            $this->assertGreaterThan(0, $young, "Young ruling threshold should be > 0 for year {$year}");
            $this->assertEquals(0, $research, "Research ruling threshold should be 0 for year {$year}");
        }
    }

    public function testConstantsHavePayrollTax(): void
    {
        foreach ($this->constants->getYears() as $year) {
            $brackets = $this->constants->getPayrollTax($year);
            $this->assertIsArray($brackets);
            $this->assertNotEmpty($brackets, "Payroll tax brackets should not be empty for year {$year}");
        }
    }

    public function testConstantsHaveSocialPercent(): void
    {
        foreach ($this->constants->getYears() as $year) {
            $brackets = $this->constants->getSocialPercent($year);
            $this->assertIsArray($brackets);
            $this->assertNotEmpty($brackets, "Social percent brackets should not be empty for year {$year}");
        }
    }

    public function testConstantsHaveGeneralCredit(): void
    {
        foreach ($this->constants->getYears() as $year) {
            $brackets = $this->constants->getGeneralCredit($year);
            $this->assertIsArray($brackets);
            $this->assertNotEmpty($brackets, "General credit brackets should not be empty for year {$year}");
        }
    }

    public function testConstantsHaveLabourCredit(): void
    {
        foreach ($this->constants->getYears() as $year) {
            $brackets = $this->constants->getLabourCredit($year);
            $this->assertIsArray($brackets);
            $this->assertNotEmpty($brackets, "Labour credit brackets should not be empty for year {$year}");
        }
    }

    public function testConstantsHaveWorkingConstants(): void
    {
        $this->assertEquals(52, $this->constants->getWorkingWeeks());
        $this->assertEquals(255, $this->constants->getWorkingDays());
        $this->assertEquals(40, $this->constants->getDefaultWorkingHours());
    }
}
