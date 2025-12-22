<?php

namespace DutchTaxCalculator\Tests;

use DutchTaxCalculator\SalaryPaycheck;
use DutchTaxCalculator\TaxConstants;

/**
 * Tax Calculation Tests
 * 
 * Based on official Belastingdienst tax tables:
 * @link https://www.belastingdienst.nl/wps/wcm/connect/nl/personeel-en-loon/content/hulpmiddel-loonbelastingtabellen
 */
class SalaryPaycheckTest extends TestCase
{
    private TaxConstants $constants;
    private const MAXIMUM_DISCREPANCY = 0.6;
    private const ROW_INTERVAL = 25;

    protected function setUp(): void
    {
        parent::setUp();
        $this->constants = new TaxConstants(__DIR__ . '/../data/data.json');
    }

    /**
     * @dataProvider yearProvider
     */
    public function testTaxCalculationForYear(int $year): void
    {
        $csvPath = __DIR__ . "/data/test-tax-{$year}.csv";
        
        if (!file_exists($csvPath)) {
            $this->markTestSkipped("CSV file not found for year {$year}");
        }

        $csv = $this->parseCsv($csvPath);
        
        for ($i = 0; $i < count($csv); $i += self::ROW_INTERVAL) {
            $data = $csv[$i];
            
            // Test: Before retirement age
            $paycheckYounger = new SalaryPaycheck(
                [
                    'income' => $data['income'],
                    'allowance' => false,
                    'socialSecurity' => true,
                    'older' => false,
                    'hours' => 40,
                ],
                'Month',
                $year,
                ['checked' => false],
                $this->constants
            );

            $taxCreditMonth = $data['youngerWithoutPayrollTaxCredit'] - $data['youngerWithPayrollTaxCredit'];
            $netMonth = $data['income'] - $data['youngerWithPayrollTaxCredit'];

            $this->assertAround(
                $paycheckYounger->grossMonth,
                $data['income'],
                self::MAXIMUM_DISCREPANCY,
                "Year {$year}, income {$data['income']}: grossMonth mismatch for younger"
            );

            $this->assertAround(
                abs($paycheckYounger->taxWithoutCreditMonth),
                $data['youngerWithoutPayrollTaxCredit'],
                self::MAXIMUM_DISCREPANCY,
                "Year {$year}, income {$data['income']}: taxWithoutCreditMonth mismatch for younger"
            );

            $this->assertAround(
                $paycheckYounger->taxCreditMonth,
                $taxCreditMonth,
                self::MAXIMUM_DISCREPANCY,
                "Year {$year}, income {$data['income']}: taxCreditMonth mismatch for younger"
            );

            $this->assertAround(
                abs($paycheckYounger->incomeTaxMonth),
                $data['youngerWithPayrollTaxCredit'],
                self::MAXIMUM_DISCREPANCY,
                "Year {$year}, income {$data['income']}: incomeTaxMonth mismatch for younger"
            );

            $this->assertAround(
                $paycheckYounger->netMonth,
                $netMonth,
                self::MAXIMUM_DISCREPANCY,
                "Year {$year}, income {$data['income']}: netMonth mismatch for younger"
            );

            // Test: After retirement age
            $paycheckOlder = new SalaryPaycheck(
                [
                    'income' => $data['income'],
                    'allowance' => false,
                    'socialSecurity' => true,
                    'older' => true,
                    'hours' => 40,
                ],
                'Month',
                $year,
                ['checked' => false],
                $this->constants
            );

            $taxCreditMonthOlder = $data['olderWithoutPayrollTaxCredit'] - $data['olderWithPayrollTaxCredit'];
            $netMonthOlder = $data['income'] - $data['olderWithPayrollTaxCredit'];

            $this->assertAround(
                $paycheckOlder->grossMonth,
                $data['income'],
                self::MAXIMUM_DISCREPANCY,
                "Year {$year}, income {$data['income']}: grossMonth mismatch for older"
            );

            $this->assertAround(
                abs($paycheckOlder->taxWithoutCreditMonth),
                $data['olderWithoutPayrollTaxCredit'],
                self::MAXIMUM_DISCREPANCY,
                "Year {$year}, income {$data['income']}: taxWithoutCreditMonth mismatch for older"
            );

            $this->assertAround(
                $paycheckOlder->taxCreditMonth,
                $taxCreditMonthOlder,
                self::MAXIMUM_DISCREPANCY,
                "Year {$year}, income {$data['income']}: taxCreditMonth mismatch for older"
            );

            $this->assertAround(
                abs($paycheckOlder->incomeTaxMonth),
                $data['olderWithPayrollTaxCredit'],
                self::MAXIMUM_DISCREPANCY,
                "Year {$year}, income {$data['income']}: incomeTaxMonth mismatch for older"
            );

            $this->assertAround(
                $paycheckOlder->netMonth,
                $netMonthOlder,
                self::MAXIMUM_DISCREPANCY,
                "Year {$year}, income {$data['income']}: netMonth mismatch for older"
            );
        }
    }

    /**
     * Provides years to test from the constants
     */
    public static function yearProvider(): array
    {
        $constants = new TaxConstants(__DIR__ . '/../data/data.json');
        $years = [];
        
        foreach ($constants->getYears() as $year) {
            $years["Year {$year}"] = [$year];
        }
        
        return $years;
    }

    public function testBasicCalculation(): void
    {
        $paycheck = new SalaryPaycheck(
            [
                'income' => 5000,
                'allowance' => false,
                'socialSecurity' => true,
                'older' => false,
                'hours' => 40,
            ],
            'Month',
            2025,
            ['checked' => false],
            $this->constants
        );

        $this->assertEquals(60000, $paycheck->grossYear);
        $this->assertEquals(5000, $paycheck->grossMonth);
        $this->assertLessThan(0, $paycheck->incomeTax);
        $this->assertGreaterThan(0, $paycheck->netYear);
    }

    public function testThirtyPercentRuling(): void
    {
        $paycheckWithRuling = new SalaryPaycheck(
            [
                'income' => 80000,
                'allowance' => false,
                'socialSecurity' => true,
                'older' => false,
                'hours' => 40,
            ],
            'Year',
            2025,
            ['checked' => true, 'choice' => 'normal'],
            $this->constants
        );

        $paycheckWithoutRuling = new SalaryPaycheck(
            [
                'income' => 80000,
                'allowance' => false,
                'socialSecurity' => true,
                'older' => false,
                'hours' => 40,
            ],
            'Year',
            2025,
            ['checked' => false],
            $this->constants
        );

        // With 30% ruling, net should be higher
        $this->assertGreaterThan($paycheckWithoutRuling->netYear, $paycheckWithRuling->netYear);
        // Tax-free amount should be positive with ruling
        $this->assertGreaterThan(0, $paycheckWithRuling->taxFreeYear);
        // Tax-free amount should be zero without ruling
        $this->assertEquals(0, $paycheckWithoutRuling->taxFreeYear);
    }

    public function testHolidayAllowance(): void
    {
        $paycheckWithAllowance = new SalaryPaycheck(
            [
                'income' => 60000,
                'allowance' => true,
                'socialSecurity' => true,
                'older' => false,
                'hours' => 40,
            ],
            'Year',
            2025,
            ['checked' => false],
            $this->constants
        );

        $paycheckWithoutAllowance = new SalaryPaycheck(
            [
                'income' => 60000,
                'allowance' => false,
                'socialSecurity' => true,
                'older' => false,
                'hours' => 40,
            ],
            'Year',
            2025,
            ['checked' => false],
            $this->constants
        );

        // Gross allowance should be positive when enabled
        $this->assertGreaterThan(0, $paycheckWithAllowance->grossAllowance);
        $this->assertGreaterThan(0, $paycheckWithAllowance->netAllowance);
        
        // Gross allowance should be zero when disabled
        $this->assertEquals(0, $paycheckWithoutAllowance->grossAllowance);
        $this->assertEquals(0, $paycheckWithoutAllowance->netAllowance);
    }

    public function testDifferentStartFromPeriods(): void
    {
        $yearlyIncome = 60000;

        $paycheckYear = new SalaryPaycheck(
            ['income' => $yearlyIncome, 'allowance' => false, 'socialSecurity' => true, 'older' => false, 'hours' => 40],
            'Year',
            2025,
            ['checked' => false],
            $this->constants
        );

        $paycheckMonth = new SalaryPaycheck(
            ['income' => $yearlyIncome / 12, 'allowance' => false, 'socialSecurity' => true, 'older' => false, 'hours' => 40],
            'Month',
            2025,
            ['checked' => false],
            $this->constants
        );

        $paycheckWeek = new SalaryPaycheck(
            ['income' => $yearlyIncome / 52, 'allowance' => false, 'socialSecurity' => true, 'older' => false, 'hours' => 40],
            'Week',
            2025,
            ['checked' => false],
            $this->constants
        );

        // All should result in approximately the same yearly gross
        $this->assertAround($paycheckYear->grossYear, $yearlyIncome, 1);
        $this->assertAround($paycheckMonth->grossYear, $yearlyIncome, 1);
        $this->assertAround($paycheckWeek->grossYear, $yearlyIncome, 1);
    }

    public function testToArrayMethod(): void
    {
        $paycheck = new SalaryPaycheck(
            ['income' => 5000, 'allowance' => false, 'socialSecurity' => true, 'older' => false, 'hours' => 40],
            'Month',
            2025,
            ['checked' => false],
            $this->constants
        );

        $array = $paycheck->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('grossYear', $array);
        $this->assertArrayHasKey('grossMonth', $array);
        $this->assertArrayHasKey('netYear', $array);
        $this->assertArrayHasKey('netMonth', $array);
        $this->assertArrayHasKey('incomeTax', $array);
        $this->assertArrayHasKey('taxCredit', $array);
    }
}
