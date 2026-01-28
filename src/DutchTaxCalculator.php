<?php

declare(strict_types=1);

namespace DutchTaxCalculator;

use DutchTaxCalculator\Calculator\SalaryCalculator;
use DutchTaxCalculator\Config\TaxConfigInterface;
use DutchTaxCalculator\DTO\PaycheckResult;
use DutchTaxCalculator\DTO\RulingOptions;
use DutchTaxCalculator\DTO\SalaryInput;
use DutchTaxCalculator\Enum\Period;
use DutchTaxCalculator\Exception\InvalidYearException;
use DutchTaxCalculator\Factory\CalculatorFactory;

/**
 * Main entry point for the Dutch Tax Calculator.
 *
 * This class provides a simple, clean API for calculating Dutch income tax,
 * social security contributions, and tax credits. It's framework-agnostic
 * and can be used in any PHP project.
 *
 * @example
 * ```php
 * $calculator = new DutchTaxCalculator();
 *
 * $result = $calculator->calculate(
 *     input: new SalaryInput(
 *         income: 60000.00,
 *         includeHolidayAllowance: true,
 *         socialSecurity: true,
 *         reachedRetirementAge: false,
 *         hoursPerWeek: 40.0
 *     ),
 *     period: Period::Year,
 *     year: 2026
 * );
 *
 * echo $result->netYear;
 * echo $result->effectiveTaxRate;
 * ```
 */
final class DutchTaxCalculator
{
    private SalaryCalculator $calculator;

    private TaxConfigInterface $config {
        get {
            return $this->config;
        }
    }

    public function __construct(?string $dataPath = null)
    {
        $factory = new CalculatorFactory($dataPath);
        $this->calculator = $factory->createSalaryCalculator();
        $this->config = $factory->config;
    }

    /**
     * Create with a custom configuration provider.
     */
    public static function withConfig(TaxConfigInterface $config): self
    {
        $factory = CalculatorFactory::withConfig($config);
        $instance = new self();
        $instance->calculator = $factory->createSalaryCalculator();
        $instance->config = $config;

        return $instance;
    }

    /**
     * Calculate complete paycheck from salary input.
     *
     * @param SalaryInput $input Salary input (income, hours, options)
     * @param Period $period Input period (Year, Month, Week, Day, Hour)
     * @param int $year Tax year to use for calculation
     * @param RulingOptions|null $ruling Optional 30% ruling options
     *
     * @throws InvalidYearException
     *
     * @return PaycheckResult Complete calculation result with all amounts
     */
    public function calculate(
        SalaryInput $input,
        Period $period,
        int $year,
        ?RulingOptions $ruling = null,
    ): PaycheckResult {
        return $this->calculator->calculate(
            $input,
            $period,
            $year,
            $ruling ?? RulingOptions::disabled(),
            $this->config,
        );
    }

    /**
     * Get all supported tax years.
     *
     * @return list<int>
     */
    public function getSupportedYears(): array
    {
        return $this->config->getSupportedYears();
    }

    /**
     * Get the current (latest) tax year.
     */
    public function getCurrentYear(): int
    {
        return $this->config->getCurrentYear();
    }

    /**
     * Check if a year is supported.
     */
    public function isYearSupported(int $year): bool
    {
        return $this->config->isYearSupported($year);
    }

    /**
     * Get the number of working weeks per year.
     */
    public function getWorkingWeeks(): int
    {
        return $this->config->getWorkingWeeks();
    }

    /**
     * Get the number of working days per year.
     */
    public function getWorkingDays(): int
    {
        return $this->config->getWorkingDays();
    }

    /**
     * Get the default working hours per week.
     */
    public function getDefaultWorkingHours(): int
    {
        return $this->config->getDefaultWorkingHours();
    }
}
