<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Exception;

/**
 * Exception thrown for invalid income values.
 */
final class InvalidIncomeException extends TaxCalculationException
{
    /**
     * Create exception for negative income.
     */
    public static function negativeIncome(float $income): self
    {
        return new self(\sprintf(
            'Income cannot be negative. Received: %.2f',
            $income,
        ));
    }

    /**
     * Create exception for invalid working hours.
     */
    public static function invalidWorkingHours(float $hours): self
    {
        return new self(\sprintf(
            'Working hours must be between 0 and 168 per week. Received: %.2f',
            $hours,
        ));
    }
}
