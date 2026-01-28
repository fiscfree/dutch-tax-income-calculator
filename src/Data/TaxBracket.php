<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Data;

/**
 * Value object representing a single tax bracket.
 *
 * Tax brackets define income ranges and their associated tax rates.
 * Each bracket has a minimum threshold and an optional maximum threshold.
 * The rate can be a percentage (0-1) or a fixed amount (>1 or <0).
 */
final readonly class TaxBracket
{
    public function __construct(
        public int $bracket,
        public float $min,
        public ?float $max,
        public float $rate,
        public ?float $socialRate = null,
        public ?float $olderRate = null,
    ) {
    }

    /**
     * Create from legacy array format.
     *
     * @param array{bracket?: int, min: float|int, max?: float|int|null, rate: float|int, social?: float|int, older?: float|int} $data
     */
    public static function fromArray(array $data, int $defaultBracket = 1): self
    {
        return new self(
            bracket: (int) ($data['bracket'] ?? $defaultBracket),
            min: (float) $data['min'],
            max: isset($data['max']) ? (float) $data['max'] : null,
            rate: (float) $data['rate'],
            socialRate: isset($data['social']) ? (float) $data['social'] : null,
            olderRate: isset($data['older']) ? (float) $data['older'] : null,
        );
    }

    /**
     * Get the delta (range) of this bracket.
     */
    public function getDelta(): float
    {
        if ($this->max === null) {
            return PHP_FLOAT_MAX;
        }

        return $this->max - $this->min;
    }

    /**
     * Get rate for the specified type.
     */
    public function getRateForType(string $type): float
    {
        return match ($type) {
            'social' => $this->socialRate ?? $this->rate,
            'older' => $this->olderRate ?? $this->rate,
            default => $this->rate,
        };
    }
}
