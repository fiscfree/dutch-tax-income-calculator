<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Tests\Unit;

use DutchTaxCalculator\Calculator\BracketCalculator;
use DutchTaxCalculator\Data\TaxBracket;
use DutchTaxCalculator\Tests\TestCase;

final class BracketCalculatorTest extends TestCase
{
    public function testCalculateWithPercentageRates(): void
    {
        // Brackets with explicit max values
        $brackets = [
            new TaxBracket(1, 0, 10000.0, 0.10),
            new TaxBracket(2, 10001, 20000.0, 0.20),
            new TaxBracket(3, 20001, null, 0.30),
        ];

        // Income fully in first bracket
        $result = BracketCalculator::calculate($brackets, 5000.0);
        $this->assertEquals(500.0, $result); // 5000 * 0.10

        // Income spanning first two brackets
        // First bracket: delta = 10000, salary 15000 > delta, so 10000 * 0.10 = 1000, salary becomes 5000
        // Second bracket: delta = 9999, salary 5000 < delta, so 5000 * 0.20 = 1000
        // Total = 2000
        $result = BracketCalculator::calculate($brackets, 15000.0);
        $this->assertEquals(2000.0, $result);

        // Income spanning all brackets
        // First bracket: delta = 10000, salary 30000 > delta, so 10000 * 0.10 = 1000, salary becomes 20000
        // Second bracket: delta = 9999, salary 20000 > delta, so 9999 * 0.20 = 1999.8, salary becomes 10001
        // Third bracket: delta = MAX, salary 10001 < delta, so 10001 * 0.30 = 3000.3
        // Total = 1000 + 1999.8 + 3000.3 = 6000.1
        $result = BracketCalculator::calculate($brackets, 30000.0);
        $this->assertEqualsWithDelta(6000.1, $result, 0.01);
    }

    public function testCalculateWithFixedAmounts(): void
    {
        $brackets = [
            new TaxBracket(1, 0, 20000.0, 2000.0), // Fixed amount (> 1)
            new TaxBracket(2, 20001, 40000.0, -0.05), // Negative percentage (between -1 and 1)
            new TaxBracket(3, 40001, null, 0.0),
        ];

        // Income in first bracket - gets fixed amount
        $result = BracketCalculator::calculate($brackets, 15000.0);
        $this->assertEquals(2000.0, $result);

        // Income in second bracket
        // First bracket: delta = 20000, salary 30000 > delta, amount = 2000 (fixed), salary becomes 10000
        // Second bracket: delta = 19999, salary 10000 < delta, -0.05 is percentage, amount += 10000 * -0.05 = -500
        // Final result = 2000 + (-500) = 1500
        $result = BracketCalculator::calculate($brackets, 30000.0);
        $this->assertEquals(1500.0, $result);
    }

    public function testCalculateWithMultiplier(): void
    {
        $brackets = [
            new TaxBracket(1, 0, 10000.0, 0.10),
        ];

        $result = BracketCalculator::calculate($brackets, 5000.0, 'rate', 0.5);
        $this->assertEquals(250.0, $result); // 5000 * 0.10 * 0.5
    }

    public function testCalculateWithSocialRate(): void
    {
        $brackets = [
            new TaxBracket(1, 0, 10000.0, 0.37, 0.28, 0.10),
        ];

        $resultRate = BracketCalculator::calculate($brackets, 5000.0);
        $this->assertEquals(1850.0, $resultRate); // 5000 * 0.37

        $resultSocial = BracketCalculator::calculate($brackets, 5000.0, 'social');
        $this->assertEquals(1400.0, $resultSocial); // 5000 * 0.28

        $resultOlder = BracketCalculator::calculate($brackets, 5000.0, 'older');
        $this->assertEquals(500.0, $resultOlder); // 5000 * 0.10
    }

    public function testCalculateWithZeroSalary(): void
    {
        $brackets = [
            new TaxBracket(1, 0, 10000.0, 0.10),
        ];

        $result = BracketCalculator::calculate($brackets, 0.0);
        $this->assertEquals(0.0, $result);
    }

    public function testCalculateWithEmptyBrackets(): void
    {
        $result = BracketCalculator::calculate([], 5000.0);
        $this->assertEquals(0.0, $result);
    }
}
