<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Config;

use DutchTaxCalculator\Data\TaxRates;
use DutchTaxCalculator\Exception\InvalidYearException;

/**
 * Interface for tax configuration providers.
 *
 * This interface defines the contract for accessing tax data,
 * allowing different implementations (JSON, database, API, etc.).
 */
interface TaxConfigInterface
{
    /**
     * Get all supported tax years.
     *
     * @return list<int>
     */
    public function getSupportedYears(): array;

    /**
     * Get the current (latest) tax year.
     */
    public function getCurrentYear(): int;

    /**
     * Get complete tax rates for a specific year.
     *
     * @throws InvalidYearException
     */
    public function getTaxRatesForYear(int $year): TaxRates;

    /**
     * Get the number of working weeks per year.
     */
    public function getWorkingWeeks(): int;

    /**
     * Get the number of working days per year.
     */
    public function getWorkingDays(): int;

    /**
     * Get the default working hours per week.
     */
    public function getDefaultWorkingHours(): int;

    /**
     * Check if a year is supported.
     */
    public function isYearSupported(int $year): bool;
}
