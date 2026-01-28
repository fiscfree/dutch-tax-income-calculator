<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Calculator;

use DutchTaxCalculator\Data\TaxRates;

/**
 * Calculator for Social Security Contributions (Volksverzekeringen).
 *
 * Calculates contributions for:
 * - AOW (Old Age Pension)
 * - Anw (Surviving Dependants Act)
 * - Wlz (Long-term Care Act)
 *
 * Workers who have reached retirement age do not pay AOW contributions.
 *
 * @see https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/prive/werk_en_inkomen/sociale_verzekeringen/premies_volks_en_werknemersverzekeringen/volksverzekeringen/volksverzekeringen
 */
final readonly class SocialSecurityCalculator
{
    /**
     * Calculate social security contribution.
     *
     * @param float $taxableIncome Annual taxable income
     * @param TaxRates $rates Tax rates for the calculation year
     * @param bool $reachedRetirementAge Whether the worker has reached retirement age
     *
     * @return float Social security contribution (positive value)
     */
    public function calculate(
        float $taxableIncome,
        TaxRates $rates,
        bool $reachedRetirementAge = false,
    ): float {
        $rateType = $reachedRetirementAge ? 'older' : 'social';

        return BracketCalculator::calculate(
            $rates->socialPercentBrackets,
            $taxableIncome,
            $rateType,
        );
    }
}
