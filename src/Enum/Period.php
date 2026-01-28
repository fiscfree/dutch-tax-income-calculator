<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Enum;

/**
 * Salary period types for income calculations.
 */
enum Period: string
{
    case Year = 'year';
    case Month = 'month';
    case Week = 'week';
    case Day = 'day';
    case Hour = 'hour';

    /**
     * Get the multiplier to convert this period to annual amount.
     */
    public function toAnnualMultiplier(int $workingWeeks = 52, int $workingDays = 255, float $hoursPerWeek = 40.0): float
    {
        return match ($this) {
            self::Year => 1.0,
            self::Month => 12.0,
            self::Week => (float) $workingWeeks,
            self::Day => (float) $workingDays,
            self::Hour => $workingWeeks * $hoursPerWeek,
        };
    }
}
