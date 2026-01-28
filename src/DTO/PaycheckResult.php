<?php

declare(strict_types=1);

namespace DutchTaxCalculator\DTO;

/**
 * Immutable result object for salary calculations.
 *
 * Uses PHP 8.4 property hooks for computed properties like monthly/weekly amounts,
 * effective tax rates, and other derived values.
 */
final class PaycheckResult
{
    public function __construct(
        public readonly float $grossYear,
        public readonly float $grossAllowance,
        public readonly float $taxFreeYear,
        public readonly float $taxableYear,
        public readonly float $payrollTax,
        public readonly float $socialTax,
        public readonly float $labourCredit,
        public readonly float $generalCredit,
        public readonly float $netYear,
        public readonly float $netAllowance,
        private readonly int $workingWeeks = 52,
        private readonly int $workingDays = 255,
        private readonly float $hoursPerWeek = 40.0,
    ) {
    }

    // Computed gross amounts
    public float $grossMonth {
        get => $this->roundNumber($this->grossYear / 12);
    }

    public float $grossWeek {
        get => $this->roundNumber($this->grossYear / $this->workingWeeks);
    }

    public float $grossDay {
        get => $this->roundNumber($this->grossYear / $this->workingDays);
    }

    public float $grossHour {
        get => $this->roundNumber($this->grossYear / ($this->workingWeeks * $this->hoursPerWeek));
    }

    // Computed net amounts
    public float $netMonth {
        get => $this->roundNumber($this->netYear / 12);
    }

    public float $netWeek {
        get => $this->roundNumber($this->netYear / $this->workingWeeks);
    }

    public float $netDay {
        get => $this->roundNumber($this->netYear / $this->workingDays);
    }

    public float $netHour {
        get => $this->roundNumber($this->netYear / ($this->workingWeeks * $this->hoursPerWeek));
    }

    // Computed tax amounts
    public float $taxWithoutCredit {
        get => $this->roundNumber($this->payrollTax + $this->socialTax);
    }

    public float $taxWithoutCreditMonth {
        get => $this->roundNumber($this->taxWithoutCredit / 12);
    }

    public float $taxCredit {
        get => $this->roundNumber($this->labourCredit + $this->generalCredit);
    }

    public float $taxCreditMonth {
        get => $this->roundNumber($this->taxCredit / 12);
    }

    public float $incomeTax {
        get => $this->roundNumber($this->taxWithoutCredit + $this->taxCredit);
    }

    public float $incomeTaxMonth {
        get => $this->roundNumber($this->incomeTax / 12);
    }

    // Monthly tax breakdown
    public float $payrollTaxMonth {
        get => $this->roundNumber($this->payrollTax / 12);
    }

    public float $socialTaxMonth {
        get => $this->roundNumber($this->socialTax / 12);
    }

    public float $labourCreditMonth {
        get => $this->roundNumber($this->labourCredit / 12);
    }

    public float $generalCreditMonth {
        get => $this->roundNumber($this->generalCredit / 12);
    }

    // Percentages
    public float $taxFreePercent {
        get {
            if ($this->grossYear <= 0) {
                return 0.0;
            }

            return $this->roundNumber(($this->taxFreeYear / $this->grossYear) * 100);
        }
    }

    public float $effectiveTaxRate {
        get {
            if ($this->grossYear <= 0) {
                return 0.0;
            }

            return $this->roundNumber((abs($this->incomeTax) / $this->grossYear) * 100);
        }
    }

    /**
     * Round a number to 2 decimal places.
     */
    private function roundNumber(float $value): float
    {
        return round($value, 2);
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, float>
     */
    public function toArray(): array
    {
        return [
            'grossYear' => $this->grossYear,
            'grossMonth' => $this->grossMonth,
            'grossWeek' => $this->grossWeek,
            'grossDay' => $this->grossDay,
            'grossHour' => $this->grossHour,
            'grossAllowance' => $this->grossAllowance,
            'taxFreeYear' => $this->taxFreeYear,
            'taxFreePercent' => $this->taxFreePercent,
            'taxableYear' => $this->taxableYear,
            'payrollTax' => $this->payrollTax,
            'payrollTaxMonth' => $this->payrollTaxMonth,
            'socialTax' => $this->socialTax,
            'socialTaxMonth' => $this->socialTaxMonth,
            'taxWithoutCredit' => $this->taxWithoutCredit,
            'taxWithoutCreditMonth' => $this->taxWithoutCreditMonth,
            'labourCredit' => $this->labourCredit,
            'labourCreditMonth' => $this->labourCreditMonth,
            'generalCredit' => $this->generalCredit,
            'generalCreditMonth' => $this->generalCreditMonth,
            'taxCredit' => $this->taxCredit,
            'taxCreditMonth' => $this->taxCreditMonth,
            'incomeTax' => $this->incomeTax,
            'incomeTaxMonth' => $this->incomeTaxMonth,
            'netYear' => $this->netYear,
            'netAllowance' => $this->netAllowance,
            'netMonth' => $this->netMonth,
            'netWeek' => $this->netWeek,
            'netDay' => $this->netDay,
            'netHour' => $this->netHour,
            'effectiveTaxRate' => $this->effectiveTaxRate,
        ];
    }
}
