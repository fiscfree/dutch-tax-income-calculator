<?php

namespace DutchTaxCalculator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \DutchTaxCalculator\SalaryPaycheck create(array $salaryInput, string $startFrom, int $year, array $ruling = [])
 * @method static \DutchTaxCalculator\TaxConstants getConstants()
 * 
 * @see \DutchTaxCalculator\SalaryPaycheckFactory
 */
class DutchTaxCalculator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'dutch-tax-calculator';
    }
}
