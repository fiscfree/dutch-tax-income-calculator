<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Calculator;

use DutchTaxCalculator\Data\TaxRates;

/**
 * Calculator for Tax Credits (Heffingskortingen).
 *
 * Calculates:
 * - General Tax Credit (Algemene Heffingskorting)
 * - Labour Tax Credit (Arbeidskorting)
 * - Elder Credit (additional credit for retirement age workers)
 *
 * @see https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/prive/inkomstenbelasting/heffingskortingen_boxen_tarieven/heffingskortingen/
 */
final readonly class TaxCreditCalculator
{
    /**
     * Calculate General Tax Credit (Algemene Heffingskorting).
     *
     * @param float $taxableIncome Annual taxable income
     * @param TaxRates $rates Tax rates for the calculation year
     * @param bool $reachedRetirementAge Whether the worker has reached retirement age
     * @param float $socialCreditMultiplier Multiplier for social credit adjustment
     *
     * @return float General tax credit amount (positive value)
     *
     * @see https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/prive/inkomstenbelasting/heffingskortingen_boxen_tarieven/heffingskortingen/algemene_heffingskorting/
     */
    public function calculateGeneralCredit(
        float $taxableIncome,
        TaxRates $rates,
        bool $reachedRetirementAge = false,
        float $socialCreditMultiplier = 1.0,
    ): float {
        $generalCredit = BracketCalculator::calculate(
            $rates->generalCreditBrackets,
            $taxableIncome,
            'rate',
            $socialCreditMultiplier,
        );

        // Additional credit for workers who have reached retirement age
        if ($reachedRetirementAge) {
            $generalCredit += BracketCalculator::calculate(
                $rates->elderCreditBrackets,
                $taxableIncome,
            );
        }

        return $generalCredit;
    }

    /**
     * Calculate Labour Tax Credit (Arbeidskorting).
     *
     * @param float $taxableIncome Annual taxable income
     * @param TaxRates $rates Tax rates for the calculation year
     * @param float $socialCreditMultiplier Multiplier for social credit adjustment
     *
     * @return float Labour tax credit amount (positive value, 0 if below threshold)
     *
     * @see https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/prive/inkomstenbelasting/heffingskortingen_boxen_tarieven/heffingskortingen/arbeidskorting/
     */
    public function calculateLabourCredit(
        float $taxableIncome,
        TaxRates $rates,
        float $socialCreditMultiplier = 1.0,
    ): float {
        // Workers below low wage threshold are not eligible for labour credit
        if ($taxableIncome < $rates->lowWageThreshold / $socialCreditMultiplier) {
            return 0.0;
        }

        return BracketCalculator::calculate(
            $rates->labourCreditBrackets,
            $taxableIncome,
            'rate',
            $socialCreditMultiplier,
        );
    }

    /**
     * Calculate Social Security Contribution component of Tax Credit.
     *
     * This multiplier accounts for the social contribution impact on tax credits.
     * It's used to adjust labour and general credit calculations.
     *
     * @param TaxRates $rates Tax rates for the calculation year
     * @param bool $reachedRetirementAge Whether the worker has reached retirement age
     * @param bool $socialSecurity Whether social security contributions apply
     *
     * @return float Multiplier (1.0 = full rate, <1.0 = reduced)
     *
     * @see https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/prive/werk_en_inkomen/sociale_verzekeringen/premies_volks_en_werknemersverzekeringen/volksverzekeringen/hoeveel_moet_u_betalen
     */
    public function calculateSocialCreditMultiplier(
        TaxRates $rates,
        bool $reachedRetirementAge = false,
        bool $socialSecurity = true,
    ): float {
        /*
         * JSON properties for socialPercent object:
         * - rate: Higher full rate including social contributions (used for proportion)
         * - social: Percentage of social contributions (AOW + Anw + Wlz)
         * - older: Percentage for retirement age (Anw + Wlz, no AOW contribution)
         */
        $bracket = $rates->getFirstSocialBracket();

        if ($bracket === null) {
            return 1.0;
        }

        $rate = $bracket->rate;
        $socialRate = $bracket->socialRate ?? 0.0;
        $olderRate = $bracket->olderRate ?? 0.0;

        if ($rate <= 0) {
            return 1.0;
        }

        if (!$socialSecurity) {
            // Removing AOW + Anw + Wlz from total
            return ($rate - $socialRate) / $rate;
        }

        if ($reachedRetirementAge) {
            // Removing only AOW from total
            return ($rate + $olderRate - $socialRate) / $rate;
        }

        return 1.0;
    }
}
