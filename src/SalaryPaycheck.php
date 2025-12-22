<?php

namespace DutchTaxCalculator;

/**
 * Salary Paycheck Calculator
 * 
 * For calculation instructions:
 * https://www.belastingdienst.nl/wps/wcm/connect/nl/zoeken/zoeken?q=Rekenvoorschriften+voor+de+geautomatiseerde+loonadministratie
 */
class SalaryPaycheck
{
    // Gross amounts
    public float $grossYear;
    public float $grossMonth;
    public float $grossWeek;
    public float $grossDay;
    public float $grossHour;
    public float $grossAllowance;

    // Tax-free amounts
    public float $taxFreeYear;
    public float $taxFree;

    // Taxable amounts
    public float $taxableYear;

    // Tax amounts
    public float $payrollTax;
    public float $payrollTaxMonth;
    public float $socialTax;
    public float $socialTaxMonth;
    public float $taxWithoutCredit;
    public float $taxWithoutCreditMonth;

    // Tax credits
    public float $labourCredit;
    public float $labourCreditMonth;
    public float $generalCredit;
    public float $generalCreditMonth;
    public float $taxCredit;
    public float $taxCreditMonth;

    // Income tax
    public float $incomeTax;
    public float $incomeTaxMonth;

    // Net amounts
    public float $netYear;
    public float $netAllowance;
    public float $netMonth;
    public float $netWeek;
    public float $netDay;
    public float $netHour;

    private TaxConstants $constants;

    /**
     * Create a new salary paycheck calculation
     *
     * @param array $salaryInput Salary input with keys: income, allowance, socialSecurity, older, hours
     * @param string $startFrom Period type: 'Year', 'Month', 'Week', 'Day', 'Hour'
     * @param int $year Year to perform calculation
     * @param array $ruling 30% ruling with keys: checked, choice
     * @param TaxConstants|null $constants Tax constants instance
     */
    public function __construct(
        array $salaryInput,
        string $startFrom,
        int $year,
        array $ruling,
        ?TaxConstants $constants = null
    ) {
        $this->constants = $constants ?? new TaxConstants();

        $income = $salaryInput['income'] ?? 0;
        $allowance = $salaryInput['allowance'] ?? false;
        $socialSecurity = $salaryInput['socialSecurity'] ?? true;
        $older = $salaryInput['older'] ?? false;
        $hours = $salaryInput['hours'] ?? $this->constants->getDefaultWorkingHours();

        // Initialize all gross values to 0
        $this->grossYear = 0;
        $this->grossMonth = 0;
        $this->grossWeek = 0;
        $this->grossDay = 0;
        $this->grossHour = 0;

        // Set the initial income based on the period type
        $propertyName = 'gross' . $startFrom;
        $this->$propertyName = $income;

        // Calculate gross year from all period inputs
        $workingWeeks = $this->constants->getWorkingWeeks();
        $workingDays = $this->constants->getWorkingDays();

        $grossYear = $this->grossYear +
            $this->grossMonth * 12 +
            $this->grossWeek * $workingWeeks;
        $grossYear += $this->grossDay * $workingDays +
            $this->grossHour * $workingWeeks * $hours;

        if (!$grossYear || $grossYear < 0) {
            $grossYear = 0;
        }

        // Calculate holiday allowance (vakantiegeld)
        $this->grossAllowance = $allowance
            ? self::getHolidayAllowance($grossYear)
            : 0;

        $this->grossYear = self::roundNumber($grossYear, 2);
        $this->grossMonth = self::getAmountMonth($grossYear);
        $this->grossWeek = self::getAmountWeek($grossYear, $workingWeeks);
        $this->grossDay = self::getAmountDay($grossYear, $workingDays);
        $this->grossHour = self::getAmountHour($grossYear, $hours, $workingWeeks);

        // Calculate taxable income
        $this->taxFreeYear = 0;
        $this->taxableYear = $grossYear - $this->grossAllowance;

        // Apply 30% ruling if applicable
        if ($ruling['checked'] ?? false) {
            $rulingIncome = $this->constants->getRulingThreshold($year, $ruling['choice'] ?? 'normal');
            $rulingMaxSalary = $this->constants->getRulingMaxSalary($year);

            // 30% ruling only up to the salary cap
            $salaryEligibleForRuling = min($this->taxableYear, $rulingMaxSalary);
            $salaryAboveCap = max(0, $this->taxableYear - $rulingMaxSalary);

            // Calculate the 30% on eligible salary only
            $effectiveSalary = $salaryEligibleForRuling * 0.7 + $salaryAboveCap;
            $effectiveSalary = max($effectiveSalary, $rulingIncome);
            $reimbursement = $this->taxableYear - $effectiveSalary;

            if ($reimbursement > 0) {
                $this->taxFreeYear = $reimbursement;
                $this->taxableYear = $this->taxableYear - $reimbursement;
            }
        }

        $this->taxFreeYear = self::roundNumber($this->taxFreeYear, 2);
        $this->taxFree = self::getTaxFreePercent($this->taxFreeYear, $grossYear);
        $this->taxableYear = self::roundNumber($this->taxableYear, 2);

        // Calculate payroll tax
        $this->payrollTax = -1 * $this->getPayrollTax($year, $this->taxableYear);
        $this->payrollTaxMonth = self::getAmountMonth($this->payrollTax);

        // Calculate social tax
        $this->socialTax = $socialSecurity
            ? -1 * $this->getSocialTax($year, $this->taxableYear, $older)
            : 0;
        $this->socialTaxMonth = self::getAmountMonth($this->socialTax);

        // Calculate total tax without credit
        $this->taxWithoutCredit = self::roundNumber($this->payrollTax + $this->socialTax, 2);
        $this->taxWithoutCreditMonth = self::getAmountMonth($this->taxWithoutCredit);

        // Calculate tax credits
        $socialCredit = $this->getSocialCredit($year, $older, $socialSecurity);

        $this->labourCredit = $this->getLabourCredit($year, $this->taxableYear, $socialCredit);
        $this->labourCreditMonth = self::getAmountMonth($this->labourCredit);

        $this->generalCredit = $this->getGeneralCredit($year, $this->taxableYear, $older, $socialCredit);

        // Adjust general credit if necessary
        $lowWageThreshold = $this->constants->getLowWageThreshold($year);
        if (
            $this->taxWithoutCredit + $this->labourCredit + $this->generalCredit > 0 ||
            ($older && $this->taxableYear < $lowWageThreshold / $socialCredit)
        ) {
            $this->generalCredit = -1 * ($this->taxWithoutCredit + $this->labourCredit);
        }

        $this->generalCreditMonth = self::getAmountMonth($this->generalCredit);
        $this->taxCredit = self::roundNumber($this->labourCredit + $this->generalCredit, 2);
        $this->taxCreditMonth = self::getAmountMonth($this->taxCredit);

        // Calculate final income tax and net amounts
        $this->incomeTax = self::roundNumber($this->taxWithoutCredit + $this->taxCredit, 2);
        $this->incomeTaxMonth = self::getAmountMonth($this->incomeTax);

        $this->netYear = $this->taxableYear + $this->incomeTax + $this->taxFreeYear;
        $this->netAllowance = $allowance
            ? self::getHolidayAllowance($this->netYear)
            : 0;
        $this->netMonth = self::getAmountMonth($this->netYear);
        $this->netWeek = self::getAmountWeek($this->netYear, $workingWeeks);
        $this->netDay = self::getAmountDay($this->netYear, $workingDays);
        $this->netHour = self::getAmountHour($this->netYear, $hours, $workingWeeks);
    }

    /**
     * Calculate holiday allowance (Vakantiegeld - 8%)
     */
    public static function getHolidayAllowance(float $amountYear): float
    {
        return self::roundNumber($amountYear * (0.08 / 1.08), 2);
    }

    /**
     * Calculate tax-free percentage
     */
    public static function getTaxFreePercent(float $taxFreeYear, float $grossYear): float
    {
        if ($grossYear == 0) {
            return 0;
        }
        return self::roundNumber(($taxFreeYear / $grossYear) * 100, 2);
    }

    /**
     * Get monthly amount from yearly amount
     */
    public static function getAmountMonth(float $amountYear): float
    {
        return self::roundNumber($amountYear / 12, 2);
    }

    /**
     * Get weekly amount from yearly amount
     */
    public static function getAmountWeek(float $amountYear, int $workingWeeks = 52): float
    {
        return self::roundNumber($amountYear / $workingWeeks, 2);
    }

    /**
     * Get daily amount from yearly amount
     */
    public static function getAmountDay(float $amountYear, int $workingDays = 255): float
    {
        return self::roundNumber($amountYear / $workingDays, 2);
    }

    /**
     * Get hourly amount from yearly amount
     */
    public static function getAmountHour(float $amountYear, float $hours, int $workingWeeks = 52): float
    {
        return self::roundNumber($amountYear / ($workingWeeks * $hours), 2);
    }

    /**
     * Get 30% Ruling minimum income threshold
     * 
     * @link https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/prive/internationaal/werken_wonen/tijdelijk_in_een_ander_land_werken/u_komt_in_nederland_werken/30_procent_regeling/voorwaarden_30_procent_regeling/u-hebt-een-specifieke-deskundigheid
     */
    public function getRulingIncome(int $year, string $ruling): float
    {
        return $this->constants->getRulingThreshold($year, $ruling);
    }

    /**
     * Calculate Payroll Tax (Loonbelasting)
     * 
     * @link https://www.belastingdienst.nl/bibliotheek/handboeken/html/boeken/HL/stappenplan-stap_7_loonbelasting_premie_volksverzekeringen.html
     */
    public function getPayrollTax(int $year, float $salary): float
    {
        return self::getRates($this->constants->getPayrollTax($year), $salary, 'rate');
    }

    /**
     * Calculate Social Security Contribution (Volksverzekeringen - AOW, Anw, Wlz)
     * 
     * @link https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/prive/werk_en_inkomen/sociale_verzekeringen/premies_volks_en_werknemersverzekeringen/volksverzekeringen/volksverzekeringen
     */
    public function getSocialTax(int $year, float $salary, bool $older): float
    {
        return self::getRates(
            $this->constants->getSocialPercent($year),
            $salary,
            $older ? 'older' : 'social'
        );
    }

    /**
     * Calculate General Tax Credit (Algemene Heffingskorting)
     * 
     * @link https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/prive/inkomstenbelasting/heffingskortingen_boxen_tarieven/heffingskortingen/algemene_heffingskorting/
     */
    public function getGeneralCredit(int $year, float $salary, bool $older, float $multiplier = 1): float
    {
        $generalCredit = self::getRates(
            $this->constants->getGeneralCredit($year),
            $salary,
            'rate',
            $multiplier
        );

        // Additional credit for worker that reached retirement age
        if ($older) {
            $generalCredit += self::getRates(
                $this->constants->getElderCredit($year),
                $salary,
                'rate'
            );
        }

        return $generalCredit;
    }

    /**
     * Calculate Labour Tax Credit (Arbeidskorting)
     * 
     * @link https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/prive/inkomstenbelasting/heffingskortingen_boxen_tarieven/heffingskortingen/arbeidskorting/
     */
    public function getLabourCredit(int $year, float $salary, float $multiplier = 1): float
    {
        $lowWageThreshold = $this->constants->getLowWageThreshold($year);
        
        if ($salary < $lowWageThreshold / $multiplier) {
            return 0;
        }

        return self::getRates(
            $this->constants->getLabourCredit($year),
            $salary,
            'rate',
            $multiplier
        );
    }

    /**
     * Calculate Social Security Contribution component of Tax Credit
     * 
     * @link https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/prive/werk_en_inkomen/sociale_verzekeringen/premies_volks_en_werknemersverzekeringen/volksverzekeringen/hoeveel_moet_u_betalen
     */
    public function getSocialCredit(int $year, bool $older, bool $socialSecurity): float
    {
        /*
         * JSON properties for socialPercent object
         * rate: Higher full rate including social contributions to be used to get proportion
         * social: Percentage of social contributions (AOW + Anw + Wlz)
         * older: Percentage for retirement age (Anw + Wlz, no contribution to AOW)
         */
        $brackets = $this->constants->getSocialPercent($year);
        $bracket = $brackets[0] ?? ['rate' => 1, 'social' => 0, 'older' => 0];
        $percentage = 1;

        if (!$socialSecurity) {
            // Removing AOW + Anw + Wlz from total
            $percentage = ($bracket['rate'] - $bracket['social']) / $bracket['rate'];
        } elseif ($older) {
            // Removing only AOW from total
            $percentage = ($bracket['rate'] + $bracket['older'] - $bracket['social']) / $bracket['rate'];
        }

        return $percentage;
    }

    /**
     * Get right amount based on the rate brackets passed
     * 
     * @link https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/themaoverstijgend/brochures_en_publicaties/nieuwsbrief-loonheffingen-2020
     */
    public static function getRates(array $brackets, float $salary, string $kind, float $multiplier = 1): float
    {
        $amount = 0;

        foreach ($brackets as $bracket) {
            $delta = isset($bracket['max']) ? $bracket['max'] - $bracket['min'] : PHP_FLOAT_MAX;
            $tax = round(
                $multiplier * ($bracket[$kind] ?? $bracket['rate']),
                5
            );
            $isPercent = $tax != 0 && $tax > -1 && $tax < 1;

            if ($salary <= $delta) {
                if ($isPercent) {
                    $amount += self::roundNumber($salary * $tax, 2);
                } else {
                    $amount = $tax;
                }
                $amount = self::roundNumber($amount, 2);
                break;
            } else {
                if ($isPercent) {
                    $amount += self::roundNumber($delta * $tax, 2);
                } else {
                    $amount = $tax;
                }
                $salary -= $delta;
            }
        }

        return $amount;
    }

    /**
     * Round a number to the specified decimal places
     */
    public static function roundNumber(float $value, int $places = 2): float
    {
        return round($value, $places);
    }

    /**
     * Convert to array
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
            'taxFree' => $this->taxFree,
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
        ];
    }
}
