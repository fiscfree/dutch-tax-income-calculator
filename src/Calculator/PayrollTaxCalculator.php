<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Calculator;

use DutchTaxCalculator\Data\TaxRates;

/**
 * Calculator for Payroll Tax (Loonbelasting).
 *
 * @see https://www.belastingdienst.nl/bibliotheek/handboeken/html/boeken/HL/stappenplan-stap_7_loonbelasting_premie_volksverzekeringen.html
 */
final readonly class PayrollTaxCalculator
{
    /**
     * Calculate payroll tax for the given taxable income.
     *
     * @param float $taxableIncome Annual taxable income
     * @param TaxRates $rates Tax rates for the calculation year
     *
     * @return float Payroll tax amount (positive value)
     */
    public function calculate(float $taxableIncome, TaxRates $rates): float
    {
        return BracketCalculator::calculate(
            $rates->payrollTaxBrackets,
            $taxableIncome,
        );
    }
}
