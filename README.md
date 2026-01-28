# Dutch Tax Income Calculator

[![PHP Version](https://img.shields.io/badge/php-%5E8.4-blue)](https://www.php.net/releases/8.4/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

A **framework-agnostic PHP 8.4+** library for calculating Dutch income tax, social security contributions and tax credits. This package is a modern PHP implementation based on the [dutch-tax-income-calculator-npm](https://github.com/stevermeister/dutch-tax-income-calculator-npm) package.

## Features

- **Payroll Tax** (Loonbelasting) calculation
- **Social Security Contributions** (Volksverzekeringen - AOW, Anw, Wlz)
- **General Tax Credit** (Algemene Heffingskorting)
- **Labour Tax Credit** (Arbeidskorting)
- **Elder Credit** (for retirement age workers)
- **30% Ruling** (30%-regeling) for expats
- **Holiday Allowance** (Vakantiegeld - 8%)
- Support for tax years **2015-2026**
- **Framework-agnostic** - works with Laravel, Symfony or any PHP project

## Requirements

- PHP 8.4 or higher

## Installation

```bash
composer require fiscfree/dutch-tax-income-calculator
```

## Quick Start

```php
<?php

use DutchTaxCalculator\DutchTaxCalculator;
use DutchTaxCalculator\DTO\SalaryInput;
use DutchTaxCalculator\Enum\Period;

$calculator = new DutchTaxCalculator();

$result = $calculator->calculate(
    input: new SalaryInput(
        income: 60000.00,
        includeHolidayAllowance: true,
        socialSecurity: true,
        reachedRetirementAge: false,
        hoursPerWeek: 40.0
    ),
    period: Period::Year,
    year: 2026
);

echo "Gross yearly: " . $result->grossYear . "\n";
echo "Net yearly: " . $result->netYear . "\n";
echo "Net monthly: " . $result->netMonth . "\n";
echo "Income tax: " . $result->incomeTax . "\n";
echo "Effective tax rate: " . $result->effectiveTaxRate . "%\n";
```

## Usage Examples

### Basic Calculation

```php
use DutchTaxCalculator\DutchTaxCalculator;
use DutchTaxCalculator\DTO\SalaryInput;
use DutchTaxCalculator\Enum\Period;

$calculator = new DutchTaxCalculator();

// Calculate from monthly salary
$result = $calculator->calculate(
    input: new SalaryInput(income: 5000.00),
    period: Period::Month,
    year: 2026
);

// Access results
$result->grossYear;      // Annual gross income
$result->grossMonth;     // Monthly gross income
$result->netYear;        // Annual net income
$result->netMonth;       // Monthly net income
$result->incomeTax;      // Total income tax (negative)
$result->payrollTax;     // Payroll tax (negative)
$result->socialTax;      // Social security (negative)
$result->labourCredit;   // Labour credit (positive)
$result->generalCredit;  // General credit (positive)
```

### With 30% Ruling

```php
use DutchTaxCalculator\DutchTaxCalculator;
use DutchTaxCalculator\DTO\SalaryInput;
use DutchTaxCalculator\DTO\RulingOptions;
use DutchTaxCalculator\Enum\Period;
use DutchTaxCalculator\Enum\RulingType;

$calculator = new DutchTaxCalculator();

// Using factory method (recommended)
$result = $calculator->calculate(
    input: new SalaryInput(income: 80000.00),
    period: Period::Year,
    year: 2026,
    ruling: RulingOptions::enabled()  // or enabled(RulingType::YoungMaster)
);

// Or using constructor
$result = $calculator->calculate(
    input: new SalaryInput(income: 80000.00),
    period: Period::Year,
    year: 2026,
    ruling: new RulingOptions(
        enabled: true,
        type: RulingType::Normal  // Normal, YoungMaster or Research
    )
);

echo "Tax-free amount: " . $result->taxFreeYear . "\n";
echo "Tax-free percentage: " . $result->taxFreePercent . "%\n";
```

### Different Input Periods

```php
// From yearly salary
$calculator->calculate(
    input: new SalaryInput(income: 60000.00),
    period: Period::Year,
    year: 2026
);

// From monthly salary
$calculator->calculate(
    input: new SalaryInput(income: 5000.00),
    period: Period::Month,
    year: 2026
);

// From weekly salary
$calculator->calculate(
    input: new SalaryInput(income: 1154.00),
    period: Period::Week,
    year: 2026
);

// From hourly rate
$calculator->calculate(
    input: new SalaryInput(income: 28.85, hoursPerWeek: 40.0),
    period: Period::Hour,
    year: 2026
);
```

### Retirement Age Workers

```php
$result = $calculator->calculate(
    input: new SalaryInput(
        income: 50000.00,
        reachedRetirementAge: true  // No AOW contribution
    ),
    period: Period::Year,
    year: 2026
);
```

### Without Social Security

```php
$result = $calculator->calculate(
    input: new SalaryInput(
        income: 50000.00,
        socialSecurity: false  // No volksverzekeringen
    ),
    period: Period::Year,
    year: 2026
);
```

## Result Object

The `PaycheckResult` object provides comprehensive access to all calculated values:

| Property | Description |
|----------|-------------|
| `grossYear` | Annual gross income |
| `grossMonth` | Monthly gross income |
| `grossWeek` | Weekly gross income |
| `grossDay` | Daily gross income |
| `grossHour` | Hourly gross income |
| `grossAllowance` | Holiday allowance (vakantiegeld) |
| `taxFreeYear` | Tax-free amount (30% ruling) |
| `taxFreePercent` | Tax-free percentage |
| `taxableYear` | Annual taxable income |
| `payrollTax` | Payroll tax (negative) |
| `socialTax` | Social security (negative) |
| `taxWithoutCredit` | Total tax before credits |
| `labourCredit` | Labour credit (positive) |
| `generalCredit` | General credit (positive) |
| `taxCredit` | Total tax credits |
| `incomeTax` | Final income tax |
| `netYear` | Annual net income |
| `netMonth` | Monthly net income |
| `netWeek` | Weekly net income |
| `netDay` | Daily net income |
| `netHour` | Hourly net income |
| `netAllowance` | Net holiday allowance |
| `effectiveTaxRate` | Effective tax rate (%) |

## Framework Integration

### Laravel

Register as a singleton in a service provider:

```php
// AppServiceProvider.php
public function register(): void
{
    $this->app->singleton(
        \DutchTaxCalculator\DutchTaxCalculator::class,
        fn() => new \DutchTaxCalculator\DutchTaxCalculator()
    );
}
```

### Symfony

Define as a service:

```yaml
# services.yaml
services:
    DutchTaxCalculator\DutchTaxCalculator: ~
```

## Supported Tax Years

The calculator supports tax years 2015-2026. Tax data is sourced from official Belastingdienst publications.

```php
$calculator->getSupportedYears();  // [2015, 2016, ..., 2026]
$calculator->getCurrentYear();     // 2026
$calculator->isYearSupported(2025); // true
```

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer phpstan

# Fix code style
composer cs-fix
```

## Testing

The calculator is tested against official Belastingdienst tax tables for all supported years:

```bash
vendor/bin/phpunit
```

## References

- [Rekenvoorschriften voor de geautomatiseerde loonadministratie](https://www.belastingdienst.nl/wps/wcm/connect/nl/zoeken/zoeken?q=Rekenvoorschriften+voor+de+geautomatiseerde+loonadministratie)
- [Loonbelastingtabellen](https://www.belastingdienst.nl/wps/wcm/connect/nl/personeel-en-loon/content/hulpmiddel-loonbelastingtabellen)
- [30%-regeling](https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/prive/internationaal/werken_wonen/tijdelijk_in_een_ander_land_werken/u_komt_in_nederland_werken/30_procent_regeling/)

## Credits

- Original JavaScript implementation: [dutch-tax-income-calculator-npm](https://github.com/stevermeister/dutch-tax-income-calculator-npm)
- Tax data source: [Belastingdienst](https://www.belastingdienst.nl/)

## License

MIT License - see [LICENSE](LICENSE) file for details.