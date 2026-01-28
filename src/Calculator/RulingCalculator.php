<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Calculator;

use DutchTaxCalculator\Data\TaxRates;
use DutchTaxCalculator\DTO\RulingOptions;
use DutchTaxCalculator\Enum\RulingType;

/**
 * Calculator for 30% Ruling (30%-regeling).
 *
 * The 30% ruling is a Dutch tax advantage for highly skilled migrants
 * working in the Netherlands. Under this ruling, employers can provide
 * 30% of the employee's salary as a tax-free allowance.
 *
 * @see https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/prive/internationaal/werken_wonen/tijdelijk_in_een_ander_land_werken/u_komt_in_nederland_werken/30_procent_regeling/voorwaarden_30_procent_regeling/u-hebt-een-specifieke-deskundigheid
 */
final readonly class RulingCalculator
{
    /**
     * Calculate the tax-free amount under 30% ruling.
     *
     * @param float $grossIncome Annual gross income (excluding holiday allowance)
     * @param RulingOptions $options 30% ruling options
     * @param TaxRates $rates Tax rates for the calculation year
     *
     * @return float Tax-free amount (0 if ruling doesn't apply or income below threshold)
     */
    public function calculateTaxFreeAmount(
        float $grossIncome,
        RulingOptions $options,
        TaxRates $rates,
    ): float {
        if (!$options->enabled) {
            return 0.0;
        }

        // Get the minimum income threshold for the ruling type
        $threshold = $this->getThreshold($options->type, $rates);

        // Get the maximum salary eligible for the 30% ruling
        $maxSalary = $rates->rulingMaxSalary;

        // 30% ruling only applies up to the salary cap
        $salaryEligibleForRuling = min($grossIncome, $maxSalary);
        $salaryAboveCap = max(0, $grossIncome - $maxSalary);

        // Calculate the effective salary (70% of eligible + 100% of above cap)
        $effectiveSalary = $salaryEligibleForRuling * 0.7 + $salaryAboveCap;

        // Effective salary cannot be below the threshold
        $effectiveSalary = max($effectiveSalary, $threshold);

        // Tax-free amount is the difference
        $reimbursement = $grossIncome - $effectiveSalary;

        return max(0.0, round($reimbursement, 2));
    }

    /**
     * Get the income threshold for a specific ruling type.
     */
    private function getThreshold(RulingType $type, TaxRates $rates): float
    {
        return match ($type) {
            RulingType::Normal => $rates->rulingThresholdNormal,
            RulingType::YoungMaster => $rates->rulingThresholdYoung,
            RulingType::Research => $rates->rulingThresholdResearch,
        };
    }
}
