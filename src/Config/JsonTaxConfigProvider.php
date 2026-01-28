<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Config;

use DutchTaxCalculator\Data\TaxBracket;
use DutchTaxCalculator\Data\TaxRates;
use DutchTaxCalculator\Exception\InvalidYearException;
use InvalidArgumentException;

/**
 * Tax configuration provider that loads data from JSON file.
 *
 * This implementation reads tax data from a JSON file and provides
 * strongly-typed access to tax rates, brackets, and thresholds.
 */
final class JsonTaxConfigProvider implements TaxConfigInterface
{
    /** @var array{currentYear: int, years: list<int>, workingWeeks: int, workingDays: int, defaultWorkingHours: int, rulingThreshold: array<int, array{normal: float|int, young: float|int, research: float|int}>, rulingMaxSalary: array<int, float|int>, payrollTax: array<int, list<array<string, mixed>>>, socialPercent: array<int, list<array<string, mixed>>>, generalCredit: array<int, list<array<string, mixed>>>, labourCredit: array<int, list<array<string, mixed>>>, elderCredit: array<int, list<array<string, mixed>>>, lowWageThreshold: array<int, float|int>} */
    private array $data;

    /** @var array<int, TaxRates> */
    private array $cachedRates = [];

    public function __construct(?string $dataPath = null)
    {
        $dataPath ??= __DIR__ . '/../../data/data.json';

        if (!file_exists($dataPath)) {
            throw new InvalidArgumentException("Tax data file not found: $dataPath");
        }

        $jsonContent = file_get_contents($dataPath);

        if ($jsonContent === false) {
            throw new InvalidArgumentException("Failed to read tax data file: $dataPath");
        }

        if (!json_validate($jsonContent)) {
            throw new InvalidArgumentException('Invalid JSON in tax data file: ' . json_last_error_msg());
        }

        /** @var array{currentYear: int, years: list<int>, workingWeeks: int, workingDays: int, defaultWorkingHours: int, rulingThreshold: array<int, array{normal: float|int, young: float|int, research: float|int}>, rulingMaxSalary: array<int, float|int>, payrollTax: array<int, list<array<string, mixed>>>, socialPercent: array<int, list<array<string, mixed>>>, generalCredit: array<int, list<array<string, mixed>>>, labourCredit: array<int, list<array<string, mixed>>>, elderCredit: array<int, list<array<string, mixed>>>, lowWageThreshold: array<int, float|int>} $data */
        $data = json_decode($jsonContent, true);
        $this->data = $data;
    }

    public function getSupportedYears(): array
    {
        return $this->data['years'];
    }

    public function getCurrentYear(): int
    {
        return $this->data['currentYear'];
    }

    public function getWorkingWeeks(): int
    {
        return $this->data['workingWeeks'];
    }

    public function getWorkingDays(): int
    {
        return $this->data['workingDays'];
    }

    public function getDefaultWorkingHours(): int
    {
        return $this->data['defaultWorkingHours'];
    }

    public function isYearSupported(int $year): bool
    {
        return \in_array($year, $this->getSupportedYears(), true);
    }

    public function getTaxRatesForYear(int $year): TaxRates
    {
        if (!$this->isYearSupported($year)) {
            throw InvalidYearException::yearNotSupported($year, $this->getSupportedYears());
        }

        if (isset($this->cachedRates[$year])) {
            return $this->cachedRates[$year];
        }

        $rulingThreshold = $this->data['rulingThreshold'][$year] ?? ['normal' => 0, 'young' => 0, 'research' => 0];
        $rulingMaxSalary = $this->data['rulingMaxSalary'][$year] ?? PHP_FLOAT_MAX;
        $lowWageThreshold = $this->data['lowWageThreshold'][$year] ?? 0;

        $rates = new TaxRates(
            payrollTaxBrackets: $this->parseBrackets($this->data['payrollTax'][$year] ?? []),
            socialPercentBrackets: $this->parseBrackets($this->data['socialPercent'][$year] ?? []),
            generalCreditBrackets: $this->parseBrackets($this->data['generalCredit'][$year] ?? []),
            labourCreditBrackets: $this->parseBrackets($this->data['labourCredit'][$year] ?? []),
            elderCreditBrackets: $this->parseBrackets($this->data['elderCredit'][$year] ?? []),
            rulingThresholdNormal: (float) $rulingThreshold['normal'],
            rulingThresholdYoung: (float) $rulingThreshold['young'],
            rulingThresholdResearch: (float) $rulingThreshold['research'],
            rulingMaxSalary: (float) $rulingMaxSalary,
            lowWageThreshold: (float) $lowWageThreshold,
        );

        $this->cachedRates[$year] = $rates;

        return $rates;
    }

    /**
     * Parse raw bracket data into TaxBracket objects.
     *
     * @param list<array<string, mixed>> $brackets
     *
     * @return list<TaxBracket>
     */
    private function parseBrackets(array $brackets): array
    {
        $result = [];
        $index = 1;

        foreach ($brackets as $bracket) {
            /** @var array{bracket?: int, min: float|int, max?: float|int|null, rate: float|int, social?: float|int, older?: float|int} $bracket */
            $result[] = TaxBracket::fromArray($bracket, $index);
            $index++;
        }

        return $result;
    }

    /**
     * Get raw data array (for debugging or legacy compatibility).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
