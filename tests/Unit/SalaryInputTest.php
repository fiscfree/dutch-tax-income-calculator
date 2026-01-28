<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Tests\Unit;

use DutchTaxCalculator\DTO\SalaryInput;
use DutchTaxCalculator\Exception\InvalidIncomeException;
use DutchTaxCalculator\Tests\TestCase;

final class SalaryInputTest extends TestCase
{
    /**
     * @throws InvalidIncomeException
     */
    public function testConstructWithDefaults(): void
    {
        $input = new SalaryInput(income: 50000.0);

        $this->assertEquals(50000.0, $input->income);
        $this->assertFalse($input->includeHolidayAllowance);
        $this->assertTrue($input->socialSecurity);
        $this->assertFalse($input->reachedRetirementAge);
        $this->assertEquals(40.0, $input->hoursPerWeek);
    }

    /**
     * @throws InvalidIncomeException
     */
    public function testConstructWithAllParameters(): void
    {
        $input = new SalaryInput(
            income: 75000.0,
            includeHolidayAllowance: true,
            socialSecurity: false,
            reachedRetirementAge: true,
            hoursPerWeek: 32.0,
        );

        $this->assertEquals(75000.0, $input->income);
        $this->assertTrue($input->includeHolidayAllowance);
        $this->assertFalse($input->socialSecurity);
        $this->assertTrue($input->reachedRetirementAge);
        $this->assertEquals(32.0, $input->hoursPerWeek);
    }

    public function testNegativeIncomeThrowsException(): void
    {
        $this->expectException(InvalidIncomeException::class);
        $this->expectExceptionMessage('Income cannot be negative');

        new SalaryInput(income: -1000.0);
    }

    public function testInvalidWorkingHoursThrowsException(): void
    {
        $this->expectException(InvalidIncomeException::class);
        $this->expectExceptionMessage('Working hours must be between 0 and 168');

        new SalaryInput(income: 50000.0, hoursPerWeek: 200.0);
    }

    /**
     * @throws InvalidIncomeException
     */
    public function testWithIncome(): void
    {
        $original = new SalaryInput(
            income: 50000.0,
            includeHolidayAllowance: true,
            socialSecurity: false,
            reachedRetirementAge: true,
            hoursPerWeek: 32.0,
        );

        $modified = $original->withIncome(75000.0);

        // Original unchanged
        $this->assertEquals(50000.0, $original->income);

        // Modified has new income but same other properties
        $this->assertEquals(75000.0, $modified->income);
        $this->assertTrue($modified->includeHolidayAllowance);
        $this->assertFalse($modified->socialSecurity);
        $this->assertTrue($modified->reachedRetirementAge);
        $this->assertEquals(32.0, $modified->hoursPerWeek);
    }

    /**
     * @throws InvalidIncomeException
     */
    public function testZeroIncomeIsValid(): void
    {
        $input = new SalaryInput(income: 0.0);

        $this->assertEquals(0.0, $input->income);
    }

    /**
     * @throws InvalidIncomeException
     */
    public function testZeroWorkingHoursIsValid(): void
    {
        $input = new SalaryInput(income: 50000.0, hoursPerWeek: 0.0);

        $this->assertEquals(0.0, $input->hoursPerWeek);
    }
}
