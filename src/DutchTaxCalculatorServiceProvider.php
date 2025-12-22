<?php

namespace DutchTaxCalculator;

use Illuminate\Support\ServiceProvider;

class DutchTaxCalculatorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/dutch-tax-calculator.php' => config_path('dutch-tax-calculator.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../data/data.json' => resource_path('dutch-tax-calculator/data.json'),
        ], 'data');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/dutch-tax-calculator.php',
            'dutch-tax-calculator'
        );

        $this->app->singleton(TaxConstants::class, function ($app) {
            $dataPath = config('dutch-tax-calculator.data_path', __DIR__ . '/../data/data.json');
            return new TaxConstants($dataPath);
        });

        $this->app->bind('dutch-tax-calculator', function ($app) {
            return new SalaryPaycheckFactory($app->make(TaxConstants::class));
        });
    }
}
