<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Exception;

/**
 * Exception thrown when an unsupported tax year is requested.
 */
final class InvalidYearException extends TaxCalculationException
{
    /**
     * Create exception for an unsupported year.
     *
     * @param list<int> $supportedYears
     */
    public static function yearNotSupported(int $year, array $supportedYears): self
    {
        return new self(\sprintf(
            'Tax year %d is not supported. Supported years: %s',
            $year,
            implode(', ', $supportedYears),
        ));
    }
}
