<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Calculator;

use DutchTaxCalculator\Config\TaxConfigInterface;
use DutchTaxCalculator\DTO\PaycheckResult;
use DutchTaxCalculator\DTO\RulingOptions;
use DutchTaxCalculator\DTO\SalaryInput;
use DutchTaxCalculator\Enum\Period;
use DutchTaxCalculator\Exception\InvalidYearException;

/**
 * Main salary calculator that orchestrates all tax calculations.
 *
 * This class combines all individual calculators to produce a complete
 * paycheck calculation, including gross amounts, taxes, credits, and net amounts.
 *
 * @see https://www.belastingdienst.nl/wps/wcm/connect/nl/zoeken/zoeken?q=Rekenvoorschriften+voor+de+geautomatiseerde+loonadministratie
 */
final readonly class SalaryCalculator
{
    public function __construct(
        private PayrollTaxCalculator $payrollTaxCalculator,
        private SocialSecurityCalculator $socialSecurityCalculator,
        private TaxCreditCalculator $taxCreditCalculator,
        private RulingCalculator $rulingCalculator,
    ) {
    }

    /**
     * Calculate complete paycheck from input.
     *
     * @throws InvalidYearException
     */
    public function calculate(
        SalaryInput $input,
        Period $period,
        int $year,
        RulingOptions $ruling,
        TaxConfigInterface $config,
    ): PaycheckResult {
        $rates = $config->getTaxRatesForYear($year);
        $workingWeeks = $config->getWorkingWeeks();
        $workingDays = $config->getWorkingDays();

        // Convert input income to annual gross
        $grossYear = $this->toAnnualGross(
            $input->income,
            $period,
            $workingWeeks,
            $workingDays,
            $input->hoursPerWeek,
        );

        if ($grossYear < 0) {
            $grossYear = 0.0;
        }

        // Calculate holiday allowance (Vakantiegeld - 8%)
        $grossAllowance = $input->includeHolidayAllowance
            ? $this->getHolidayAllowance($grossYear)
            : 0.0;

        // Calculate taxable income (gross minus holiday allowance)
        $taxableYear = $grossYear - $grossAllowance;

        // Apply 30% ruling if applicable
        $taxFreeYear = $this->rulingCalculator->calculateTaxFreeAmount(
            $taxableYear,
            $ruling,
            $rates,
        );
        $taxableYear -= $taxFreeYear;

        // Calculate payroll tax
        $payrollTax = -1 * $this->payrollTaxCalculator->calculate($taxableYear, $rates);

        // Calculate social security tax
        $socialTax = $input->socialSecurity
            ? -1 * $this->socialSecurityCalculator->calculate(
                $taxableYear,
                $rates,
                $input->reachedRetirementAge,
            )
            : 0.0;

        // Calculate total tax without credits
        $taxWithoutCredit = $this->roundNumber($payrollTax + $socialTax);

        // Calculate social credit multiplier
        $socialCredit = $this->taxCreditCalculator->calculateSocialCreditMultiplier(
            $rates,
            $input->reachedRetirementAge,
            $input->socialSecurity,
        );

        // Calculate labour credit
        $labourCredit = $this->taxCreditCalculator->calculateLabourCredit(
            $taxableYear,
            $rates,
            $socialCredit,
        );

        // Calculate general credit
        $generalCredit = $this->taxCreditCalculator->calculateGeneralCredit(
            $taxableYear,
            $rates,
            $input->reachedRetirementAge,
            $socialCredit,
        );

        // Adjust general credit if necessary
        // This prevents the total credit from exceeding the tax due
        if (
            $taxWithoutCredit + $labourCredit + $generalCredit > 0
            || ($input->reachedRetirementAge && $taxableYear < $rates->lowWageThreshold / $socialCredit)
        ) {
            $generalCredit = -1 * ($taxWithoutCredit + $labourCredit);
        }

        // Calculate final income tax
        $incomeTax = $this->roundNumber($taxWithoutCredit + $labourCredit + $generalCredit);

        // Calculate net year
        $netYear = $taxableYear + $incomeTax + $taxFreeYear;

        // Calculate net allowance
        $netAllowance = $input->includeHolidayAllowance
            ? $this->getHolidayAllowance($netYear)
            : 0.0;

        return new PaycheckResult(
            grossYear: $this->roundNumber($grossYear),
            grossAllowance: $this->roundNumber($grossAllowance),
            taxFreeYear: $this->roundNumber($taxFreeYear),
            taxableYear: $this->roundNumber($taxableYear + $taxFreeYear),
            payrollTax: $this->roundNumber($payrollTax),
            socialTax: $this->roundNumber($socialTax),
            labourCredit: $this->roundNumber($labourCredit),
            generalCredit: $this->roundNumber($generalCredit),
            netYear: $this->roundNumber($netYear),
            netAllowance: $this->roundNumber($netAllowance),
            workingWeeks: $workingWeeks,
            workingDays: $workingDays,
            hoursPerWeek: $input->hoursPerWeek,
        );
    }

    /**
     * Convert income from any period to annual gross.
     */
    private function toAnnualGross(
        float $income,
        Period $period,
        int $workingWeeks,
        int $workingDays,
        float $hoursPerWeek,
    ): float {
        return $income * $period->toAnnualMultiplier($workingWeeks, $workingDays, $hoursPerWeek);
    }

    /**
     * Calculate holiday allowance (Vakantiegeld - 8%).
     *
     * The formula divides by 1.08 because the gross amount already includes
     * the holiday allowance when it's factored in.
     */
    private function getHolidayAllowance(float $annualAmount): float
    {
        return $this->roundNumber($annualAmount * (0.08 / 1.08));
    }

    /**
     * Round a number to 2 decimal places.
     */
    private function roundNumber(float $value): float
    {
        return round($value, 2);
    }
}
