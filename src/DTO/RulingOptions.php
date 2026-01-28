<?php

declare(strict_types=1);

namespace DutchTaxCalculator\DTO;

use DutchTaxCalculator\Enum\RulingType;

/**
 * Data transfer object for 30% ruling options.
 *
 * The 30% ruling (30%-regeling) is a Dutch tax advantage for highly skilled
 * migrants, allowing them to receive 30% of their salary tax-free.
 */
final readonly class RulingOptions
{
    public function __construct(
        public bool $enabled,
        public RulingType $type = RulingType::Normal,
    ) {
    }

    /**
     * Create disabled ruling options (no 30% ruling applied).
     */
    public static function disabled(): self
    {
        return new self(enabled: false);
    }

    /**
     * Create enabled ruling options with specified type.
     */
    public static function enabled(RulingType $type = RulingType::Normal): self
    {
        return new self(enabled: true, type: $type);
    }
}
