<?php

namespace DutchTaxCalculator;

use InvalidArgumentException;

/**
 * Tax Constants Manager
 * 
 * Loads and provides access to Dutch tax rates, thresholds, and brackets
 * from the data.json configuration file.
 */
class TaxConstants
{
    private array $data;

    public function __construct(?string $dataPath = null)
    {
        $dataPath = $dataPath ?? __DIR__ . '/../data/data.json';
        
        if (!file_exists($dataPath)) {
            throw new InvalidArgumentException("Tax data file not found: {$dataPath}");
        }

        $jsonContent = file_get_contents($dataPath);
        $this->data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException("Invalid JSON in tax data file: " . json_last_error_msg());
        }
    }

    public function getCurrentYear(): int
    {
        return $this->data['currentYear'];
    }

    public function getYears(): array
    {
        return $this->data['years'];
    }

    public function getDefaultWorkingHours(): int
    {
        return $this->data['defaultWorkingHours'];
    }

    public function getWorkingWeeks(): int
    {
        return $this->data['workingWeeks'];
    }

    public function getWorkingDays(): int
    {
        return $this->data['workingDays'];
    }

    public function getRulingThreshold(int $year, string $ruling): float
    {
        return $this->data['rulingThreshold'][$year][$ruling] ?? 0;
    }

    public function getRulingMaxSalary(int $year): float
    {
        return $this->data['rulingMaxSalary'][$year] ?? PHP_FLOAT_MAX;
    }

    public function getPayrollTax(int $year): array
    {
        return $this->data['payrollTax'][$year] ?? [];
    }

    public function getSocialPercent(int $year): array
    {
        return $this->data['socialPercent'][$year] ?? [];
    }

    public function getGeneralCredit(int $year): array
    {
        return $this->data['generalCredit'][$year] ?? [];
    }

    public function getLabourCredit(int $year): array
    {
        return $this->data['labourCredit'][$year] ?? [];
    }

    public function getLowWageThreshold(int $year): float
    {
        return $this->data['lowWageThreshold'][$year] ?? 0;
    }

    public function getElderCredit(int $year): array
    {
        return $this->data['elderCredit'][$year] ?? [];
    }

    /**
     * Get raw data array
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
