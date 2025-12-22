# Dutch Tax Income Calculator for Laravel

A Laravel package for calculating Dutch income tax, social security contributions, and tax credits (loonbelasting, volksverzekeringen, arbeidskorting, algemene heffingskorting). It is 100% based
on https://github.com/stevermeister/dutch-tax-income-calculator-npm and converted to a Laravel
package using Claude Opus 4.5. This uses the same data.json file and test csv files as the NPM
package which should make yearly updates a breeze.

## Installation

Install via Composer:

```bash
composer require fiscfree/dutch-tax-income-calculator
```

The package will auto-register its service provider in Laravel 10+.

## Usage

### Using the Facade

```php
use DutchTaxCalculator\Facades\DutchTaxCalculator;

$paycheck = DutchTaxCalculator::create(
    [
        'income' => 5000,           // Monthly income
        'allowance' => false,       // Include holiday allowance (vakantiegeld)
        'socialSecurity' => true,   // Include social security contributions
        'older' => false,           // After retirement age
        'hours' => 40,              // Working hours per week
    ],
    'Month',                        // Period: 'Year', 'Month', 'Week', 'Day', 'Hour'
    2025,                           // Tax year
    ['checked' => false]            // 30% ruling options
);

// Access calculated values
echo $paycheck->grossYear;          // Annual gross income
echo $paycheck->netMonth;           // Monthly net income
echo $paycheck->incomeTax;          // Total income tax
echo $paycheck->taxCredit;          // Total tax credits
```

### Using Dependency Injection

```php
use DutchTaxCalculator\SalaryPaycheckFactory;

class SalaryController extends Controller
{
    public function calculate(SalaryPaycheckFactory $calculator)
    {
        $paycheck = $calculator->create(
            ['income' => 60000, 'allowance' => true, 'socialSecurity' => true, 'older' => false, 'hours' => 40],
            'Year',
            2025,
            ['checked' => false]
        );

        return response()->json($paycheck->toArray());
    }
}
```

### Direct Instantiation

```php
use DutchTaxCalculator\SalaryPaycheck;
use DutchTaxCalculator\TaxConstants;

$constants = new TaxConstants();
$paycheck = new SalaryPaycheck(
    ['income' => 5000, 'allowance' => false, 'socialSecurity' => true, 'older' => false, 'hours' => 40],
    'Month',
    2025,
    ['checked' => false],
    $constants
);
```

### 30% Ruling (30%-regeling)

For expatriates who qualify for the 30% ruling:

```php
$paycheck = DutchTaxCalculator::create(
    ['income' => 80000, 'allowance' => false, 'socialSecurity' => true, 'older' => false, 'hours' => 40],
    'Year',
    2025,
    [
        'checked' => true,
        'choice' => 'normal'  // Options: 'normal', 'young', 'research'
    ]
);

echo $paycheck->taxFreeYear;  // Tax-free portion of income
echo $paycheck->taxFree;      // Tax-free percentage
```

## Available Properties

| Property | Description |
|----------|-------------|
| `grossYear` | Annual gross income |
| `grossMonth` | Monthly gross income |
| `grossWeek` | Weekly gross income |
| `grossDay` | Daily gross income |
| `grossHour` | Hourly gross income |
| `grossAllowance` | Holiday allowance (vakantiegeld) |
| `taxFreeYear` | Tax-free annual amount (30% ruling) |
| `taxFree` | Tax-free percentage |
| `taxableYear` | Taxable annual income |
| `payrollTax` | Payroll tax (loonbelasting) |
| `socialTax` | Social security contributions |
| `taxWithoutCredit` | Tax before credits |
| `labourCredit` | Labour tax credit (arbeidskorting) |
| `generalCredit` | General tax credit (algemene heffingskorting) |
| `taxCredit` | Total tax credits |
| `incomeTax` | Final income tax |
| `netYear` | Annual net income |
| `netMonth` | Monthly net income |
| `netWeek` | Weekly net income |
| `netDay` | Daily net income |
| `netHour` | Hourly net income |
| `netAllowance` | Net holiday allowance |

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="DutchTaxCalculator\DutchTaxCalculatorServiceProvider" --tag=config
```

To use custom tax data:

```bash
php artisan vendor:publish --provider="DutchTaxCalculator\DutchTaxCalculatorServiceProvider" --tag=data
```

Then update `config/dutch-tax-calculator.php`:

```php
return [
    'data_path' => resource_path('dutch-tax-calculator/data.json'),
];
```

## Supported Tax Years

The package includes tax data for years 2015-2026.

## Testing

```bash
composer test
```

## References

- [Rekenvoorschriften voor de geautomatiseerde loonadministratie](https://www.belastingdienst.nl/wps/wcm/connect/nl/zoeken/zoeken?q=Rekenvoorschriften+voor+de+geautomatiseerde+loonadministratie)
- [Loonbelastingtabellen](https://www.belastingdienst.nl/wps/wcm/connect/nl/personeel-en-loon/content/hulpmiddel-loonbelastingtabellen)
- [30%-regeling](https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/prive/internationaal/werken_wonen/tijdelijk_in_een_ander_land_werken/u_komt_in_nederland_werken/30_procent_regeling/)

## License

MIT License
