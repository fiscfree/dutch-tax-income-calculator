<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Factory;

use DutchTaxCalculator\Calculator\PayrollTaxCalculator;
use DutchTaxCalculator\Calculator\RulingCalculator;
use DutchTaxCalculator\Calculator\SalaryCalculator;
use DutchTaxCalculator\Calculator\SocialSecurityCalculator;
use DutchTaxCalculator\Calculator\TaxCreditCalculator;
use DutchTaxCalculator\Config\JsonTaxConfigProvider;
use DutchTaxCalculator\Config\TaxConfigInterface;

/**
 * Factory for creating calculator instances with proper dependency injection.
 *
 * This factory provides a convenient way to create fully configured
 * calculator instances without manually wiring dependencies.
 */
final class CalculatorFactory
{
    public TaxConfigInterface $config {
        get {
            return $this->config;
        }
    }

    public function __construct(?string $dataPath = null)
    {
        $this->config = new JsonTaxConfigProvider($dataPath);
    }

    /**
     * Create with a custom configuration provider.
     */
    public static function withConfig(TaxConfigInterface $config): self
    {
        $factory = new self();
        $factory->config = $config;

        return $factory;
    }

    /**
     * Create a fully configured salary calculator.
     */
    public function createSalaryCalculator(): SalaryCalculator
    {
        return new SalaryCalculator(
            payrollTaxCalculator: new PayrollTaxCalculator(),
            socialSecurityCalculator: new SocialSecurityCalculator(),
            taxCreditCalculator: new TaxCreditCalculator(),
            rulingCalculator: new RulingCalculator(),
        );
    }
}
