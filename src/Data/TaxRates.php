<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Data;

/**
 * Value object containing all tax rates for a specific year.
 *
 * This is an immutable container for all tax-related data for a given year,
 * including tax brackets, thresholds, and credit information.
 */
final readonly class TaxRates
{
    /**
     * @param list<TaxBracket> $payrollTaxBrackets Income tax (loonbelasting) brackets
     * @param list<TaxBracket> $socialPercentBrackets Social security contribution brackets
     * @param list<TaxBracket> $generalCreditBrackets General tax credit (algemene heffingskorting) brackets
     * @param list<TaxBracket> $labourCreditBrackets Labour tax credit (arbeidskorting) brackets
     * @param list<TaxBracket> $elderCreditBrackets Elder tax credit (ouderenkorting) brackets
     * @param float $rulingThresholdNormal 30% ruling threshold for normal workers
     * @param float $rulingThresholdYoung 30% ruling threshold for young masters
     * @param float $rulingThresholdResearch 30% ruling threshold for researchers
     * @param float $rulingMaxSalary Maximum salary eligible for 30% ruling
     * @param float $lowWageThreshold Threshold for labour credit eligibility
     */
    public function __construct(
        public array $payrollTaxBrackets,
        public array $socialPercentBrackets,
        public array $generalCreditBrackets,
        public array $labourCreditBrackets,
        public array $elderCreditBrackets,
        public float $rulingThresholdNormal,
        public float $rulingThresholdYoung,
        public float $rulingThresholdResearch,
        public float $rulingMaxSalary,
        public float $lowWageThreshold,
    ) {
    }

    /**
     * Get the first social security bracket (used for credit calculations).
     */
    public function getFirstSocialBracket(): ?TaxBracket
    {
        return $this->socialPercentBrackets[0] ?? null;
    }
}
