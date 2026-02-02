<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Calculator;

use DutchTaxCalculator\Data\TaxBracket;

/**
 * Core bracket calculation algorithm.
 *
 * This class extracts the progressive tax bracket calculation logic
 * from the original SalaryPaycheck::getRates() method.
 *
 * @see https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/themaoverstijgend/brochures_en_publicaties/nieuwsbrief-loonheffingen-2020
 */
final readonly class BracketCalculator
{
    /**
     * Calculate amount based on tax brackets.
     *
     * This algorithm processes progressive tax brackets, handling both
     * percentage rates and fixed amounts. It preserves the exact logic
     * from the original implementation.
     *
     * @param list<TaxBracket> $brackets Tax brackets to process
     * @param float $salary Salary amount to calculate tax for
     * @param string $rateType Type of rate to use ('rate', 'social', 'older')
     * @param float $multiplier Optional multiplier for credit calculations
     */
    public static function calculate(
        array $brackets,
        float $salary,
        string $rateType = 'rate',
        float $multiplier = 1.0,
    ): float {
        $amount = 0.0;

        foreach ($brackets as $bracket) {
            $delta = $bracket->getDelta();
            $rate = round($multiplier * $bracket->getRateForType($rateType), 5);
            $isPercent = abs($rate) >= PHP_FLOAT_EPSILON && $rate > -1 && $rate < 1;

            if ($salary <= $delta) {
                if ($isPercent) {
                    $amount += self::roundNumber($salary * $rate);
                } else {
                    $amount = $rate;
                }
                $amount = self::roundNumber($amount);

                break;
            }

            if ($isPercent) {
                $amount += self::roundNumber($delta * $rate);
            } else {
                $amount = $rate;
            }
            $salary -= $delta;
        }

        return $amount;
    }

    /**
     * Round a number to 2 decimal places.
     */
    private static function roundNumber(float $value): float
    {
        return round($value, 2);
    }
}
