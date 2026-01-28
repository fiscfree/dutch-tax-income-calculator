<?php

declare(strict_types=1);

namespace DutchTaxCalculator\DTO;

use DutchTaxCalculator\Exception\InvalidIncomeException;

/**
 * Data transfer object for salary calculation input.
 */
final readonly class SalaryInput
{
    /**
     * @throws InvalidIncomeException
     */
    public function __construct(
        public float $income,
        public bool $includeHolidayAllowance = false,
        public bool $socialSecurity = true,
        public bool $reachedRetirementAge = false,
        public float $hoursPerWeek = 40.0,
    ) {
        if ($income < 0) {
            throw InvalidIncomeException::negativeIncome($income);
        }
        if ($hoursPerWeek < 0 || $hoursPerWeek > 168) {
            throw InvalidIncomeException::invalidWorkingHours($hoursPerWeek);
        }
    }

    /**
     * Create a copy with modified income.
     *
     * @throws InvalidIncomeException
     */
    public function withIncome(float $income): self
    {
        return new self(
            income: $income,
            includeHolidayAllowance: $this->includeHolidayAllowance,
            socialSecurity: $this->socialSecurity,
            reachedRetirementAge: $this->reachedRetirementAge,
            hoursPerWeek: $this->hoursPerWeek,
        );
    }
}
