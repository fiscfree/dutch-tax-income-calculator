<?php

namespace DutchTaxCalculator;

/**
 * Factory for creating SalaryPaycheck instances
 */
class SalaryPaycheckFactory
{
    private TaxConstants $constants;

    public function __construct(TaxConstants $constants)
    {
        $this->constants = $constants;
    }

    /**
     * Create a new salary paycheck calculation
     *
     * @param array $salaryInput Salary input with keys: income, allowance, socialSecurity, older, hours
     * @param string $startFrom Period type: 'Year', 'Month', 'Week', 'Day', 'Hour'
     * @param int $year Year to perform calculation
     * @param array $ruling 30% ruling with keys: checked, choice
     * @return SalaryPaycheck
     */
    public function create(
        array $salaryInput,
        string $startFrom,
        int $year,
        array $ruling = ['checked' => false]
    ): SalaryPaycheck {
        return new SalaryPaycheck($salaryInput, $startFrom, $year, $ruling, $this->constants);
    }

    /**
     * Get the tax constants instance
     */
    public function getConstants(): TaxConstants
    {
        return $this->constants;
    }
}
