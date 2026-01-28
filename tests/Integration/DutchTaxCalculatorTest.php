<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Tests\Integration;

use DutchTaxCalculator\Config\JsonTaxConfigProvider;
use DutchTaxCalculator\DTO\RulingOptions;
use DutchTaxCalculator\DTO\SalaryInput;
use DutchTaxCalculator\DutchTaxCalculator;
use DutchTaxCalculator\Enum\Period;
use DutchTaxCalculator\Exception\InvalidIncomeException;
use DutchTaxCalculator\Exception\InvalidYearException;
use DutchTaxCalculator\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Integration tests for the Dutch Tax Calculator.
 *
 * Based on official Belastingdienst tax tables:
 *
 * @see https://www.belastingdienst.nl/wps/wcm/connect/nl/personeel-en-loon/content/hulpmiddel-loonbelastingtabellen
 */
final class DutchTaxCalculatorTest extends TestCase
{
    private DutchTaxCalculator $calculator;

    private const float MAXIMUM_DISCREPANCY = 0.6;

    private const int ROW_INTERVAL = 25;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new DutchTaxCalculator(__DIR__ . '/../../data/data.json');
    }

    /**
     * @throws InvalidYearException
     * @throws InvalidIncomeException
     */
    #[DataProvider('yearProvider')]
    public function testTaxCalculationForYear(int $year): void
    {
        $csvPath = __DIR__ . "/../data/test-tax-$year.csv";

        if (!file_exists($csvPath)) {
            $this->markTestSkipped("CSV file not found for year $year");
        }

        $csv = $this->parseCsv($csvPath);

        for ($i = 0; $i < \count($csv); $i += self::ROW_INTERVAL) {
            $data = $csv[$i];

            // Test: Before retirement age
            $resultYounger = $this->calculator->calculate(
                input: new SalaryInput(
                    income: $data['income'],
                    includeHolidayAllowance: false,
                    socialSecurity: true,
                    reachedRetirementAge: false,
                    hoursPerWeek: 40.0,
                ),
                period: Period::Month,
                year: $year,
            );

            $taxCreditMonth = $data['youngerWithoutPayrollTaxCredit'] - $data['youngerWithPayrollTaxCredit'];
            $netMonth = $data['income'] - $data['youngerWithPayrollTaxCredit'];

            $this->assertAround(
                $resultYounger->grossMonth,
                $data['income'],
                self::MAXIMUM_DISCREPANCY,
                "Year $year, income {$data['income']}: grossMonth mismatch for younger",
            );

            $this->assertAround(
                abs($resultYounger->taxWithoutCreditMonth),
                $data['youngerWithoutPayrollTaxCredit'],
                self::MAXIMUM_DISCREPANCY,
                "Year $year, income {$data['income']}: taxWithoutCreditMonth mismatch for younger",
            );

            $this->assertAround(
                $resultYounger->taxCreditMonth,
                $taxCreditMonth,
                self::MAXIMUM_DISCREPANCY,
                "Year $year, income {$data['income']}: taxCreditMonth mismatch for younger",
            );

            $this->assertAround(
                abs($resultYounger->incomeTaxMonth),
                $data['youngerWithPayrollTaxCredit'],
                self::MAXIMUM_DISCREPANCY,
                "Year $year, income {$data['income']}: incomeTaxMonth mismatch for younger",
            );

            $this->assertAround(
                $resultYounger->netMonth,
                $netMonth,
                self::MAXIMUM_DISCREPANCY,
                "Year $year, income {$data['income']}: netMonth mismatch for younger",
            );

            // Test: After retirement age
            $resultOlder = $this->calculator->calculate(
                input: new SalaryInput(
                    income: $data['income'],
                    includeHolidayAllowance: false,
                    socialSecurity: true,
                    reachedRetirementAge: true,
                    hoursPerWeek: 40.0,
                ),
                period: Period::Month,
                year: $year,
            );

            $taxCreditMonthOlder = $data['olderWithoutPayrollTaxCredit'] - $data['olderWithPayrollTaxCredit'];
            $netMonthOlder = $data['income'] - $data['olderWithPayrollTaxCredit'];

            $this->assertAround(
                $resultOlder->grossMonth,
                $data['income'],
                self::MAXIMUM_DISCREPANCY,
                "Year $year, income {$data['income']}: grossMonth mismatch for older",
            );

            $this->assertAround(
                abs($resultOlder->taxWithoutCreditMonth),
                $data['olderWithoutPayrollTaxCredit'],
                self::MAXIMUM_DISCREPANCY,
                "Year $year, income {$data['income']}: taxWithoutCreditMonth mismatch for older",
            );

            $this->assertAround(
                $resultOlder->taxCreditMonth,
                $taxCreditMonthOlder,
                self::MAXIMUM_DISCREPANCY,
                "Year $year, income {$data['income']}: taxCreditMonth mismatch for older",
            );

            $this->assertAround(
                abs($resultOlder->incomeTaxMonth),
                $data['olderWithPayrollTaxCredit'],
                self::MAXIMUM_DISCREPANCY,
                "Year $year, income {$data['income']}: incomeTaxMonth mismatch for older",
            );

            $this->assertAround(
                $resultOlder->netMonth,
                $netMonthOlder,
                self::MAXIMUM_DISCREPANCY,
                "Year $year, income {$data['income']}: netMonth mismatch for older",
            );
        }
    }

    /**
     * Provides years to test from the constants.
     *
     * @return iterable<string, array{int}>
     */
    public static function yearProvider(): iterable
    {
        $config = new JsonTaxConfigProvider(__DIR__ . '/../../data/data.json');

        foreach ($config->getSupportedYears() as $year) {
            yield "Year $year" => [$year];
        }
    }

    /**
     * @throws InvalidYearException
     * @throws InvalidIncomeException
     */
    public function testBasicCalculation(): void
    {
        $result = $this->calculator->calculate(
            input: new SalaryInput(
                income: 5000.0,
                includeHolidayAllowance: false,
                socialSecurity: true,
                reachedRetirementAge: false,
                hoursPerWeek: 40.0,
            ),
            period: Period::Month,
            year: 2025,
        );

        $this->assertEquals(60000.0, $result->grossYear);
        $this->assertEquals(5000.0, $result->grossMonth);
        $this->assertLessThan(0, $result->incomeTax);
        $this->assertGreaterThan(0, $result->netYear);
    }

    /**
     * @throws InvalidYearException
     * @throws InvalidIncomeException
     */
    public function testThirtyPercentRuling(): void
    {
        $resultWithRuling = $this->calculator->calculate(
            input: new SalaryInput(income: 80000.0),
            period: Period::Year,
            year: 2025,
            ruling: RulingOptions::enabled(),
        );

        $resultWithoutRuling = $this->calculator->calculate(
            input: new SalaryInput(income: 80000.0),
            period: Period::Year,
            year: 2025,
            ruling: RulingOptions::disabled(),
        );

        // With 30% ruling, net should be higher
        $this->assertGreaterThan($resultWithoutRuling->netYear, $resultWithRuling->netYear);
        // Tax-free amount should be positive with ruling
        $this->assertGreaterThan(0, $resultWithRuling->taxFreeYear);
        // Tax-free amount should be zero without ruling
        $this->assertEquals(0.0, $resultWithoutRuling->taxFreeYear);
    }

    /**
     * @throws InvalidYearException
     * @throws InvalidIncomeException
     */
    public function testHolidayAllowance(): void
    {
        $resultWithAllowance = $this->calculator->calculate(
            input: new SalaryInput(
                income: 60000.0,
                includeHolidayAllowance: true,
            ),
            period: Period::Year,
            year: 2025,
        );

        $resultWithoutAllowance = $this->calculator->calculate(
            input: new SalaryInput(
                income: 60000.0,
                includeHolidayAllowance: false,
            ),
            period: Period::Year,
            year: 2025,
        );

        // Gross allowance should be positive when enabled
        $this->assertGreaterThan(0, $resultWithAllowance->grossAllowance);
        $this->assertGreaterThan(0, $resultWithAllowance->netAllowance);

        // Gross allowance should be zero when disabled
        $this->assertEquals(0.0, $resultWithoutAllowance->grossAllowance);
        $this->assertEquals(0.0, $resultWithoutAllowance->netAllowance);
    }

    /**
     * @throws InvalidYearException
     * @throws InvalidIncomeException
     */
    public function testDifferentStartFromPeriods(): void
    {
        $yearlyIncome = 60000.0;

        $resultYear = $this->calculator->calculate(
            input: new SalaryInput(income: $yearlyIncome),
            period: Period::Year,
            year: 2025,
        );

        $resultMonth = $this->calculator->calculate(
            input: new SalaryInput(income: $yearlyIncome / 12),
            period: Period::Month,
            year: 2025,
        );

        $resultWeek = $this->calculator->calculate(
            input: new SalaryInput(income: $yearlyIncome / 52),
            period: Period::Week,
            year: 2025,
        );

        // All should result in approximately the same yearly gross
        $this->assertAround($resultYear->grossYear, $yearlyIncome, 1);
        $this->assertAround($resultMonth->grossYear, $yearlyIncome, 1);
        $this->assertAround($resultWeek->grossYear, $yearlyIncome, 1);
    }

    /**
     * @throws InvalidYearException
     * @throws InvalidIncomeException
     */
    public function testToArrayMethod(): void
    {
        $result = $this->calculator->calculate(
            input: new SalaryInput(income: 5000.0),
            period: Period::Month,
            year: 2025,
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('grossYear', $array);
        $this->assertArrayHasKey('grossMonth', $array);
        $this->assertArrayHasKey('netYear', $array);
        $this->assertArrayHasKey('netMonth', $array);
        $this->assertArrayHasKey('incomeTax', $array);
        $this->assertArrayHasKey('taxCredit', $array);
        $this->assertArrayHasKey('effectiveTaxRate', $array);
    }

    public function testSupportedYears(): void
    {
        $years = $this->calculator->getSupportedYears();

        $this->assertContains(2015, $years);
        $this->assertContains(2026, $years);
        $this->assertCount(12, $years);
    }

    public function testCurrentYear(): void
    {
        $currentYear = $this->calculator->getCurrentYear();

        $this->assertEquals(2026, $currentYear);
    }

    public function testYearValidation(): void
    {
        $this->assertTrue($this->calculator->isYearSupported(2025));
        $this->assertFalse($this->calculator->isYearSupported(2000));
    }

    public function testWorkingConstants(): void
    {
        $this->assertEquals(52, $this->calculator->getWorkingWeeks());
        $this->assertEquals(255, $this->calculator->getWorkingDays());
        $this->assertEquals(40, $this->calculator->getDefaultWorkingHours());
    }
}
