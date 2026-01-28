<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Parse a CSV file and return its contents as an array.
     *
     * @return list<array<string, float>>
     */
    protected function parseCsv(string $filePath): array
    {
        $result = [];
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new RuntimeException("Could not open file: $filePath");
        }

        // Read header row
        $headers = fgetcsv($handle, 0, ',', '"', '\\');

        if ($headers === false) {
            fclose($handle);

            throw new RuntimeException("Could not read headers from: $filePath");
        }

        // Read data rows
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $data = [];
            foreach ($headers as $index => $header) {
                $data[$header] = (float) ($row[$index] ?? 0);
            }
            $result[] = $data;
        }

        fclose($handle);

        return $result;
    }

    /**
     * Assert that a value is approximately equal to expected value within a difference.
     */
    protected function assertAround(
        float $actual,
        float $expected,
        float $difference = 0.1,
        string $message = '',
    ): void {
        $receivedDiff = abs($expected - $actual);

        $this->assertLessThan(
            $difference,
            $receivedDiff,
            $message !== '' ? $message : "Expected $expected, got $actual. Difference: $receivedDiff (max allowed: $difference)",
        );
    }
}
