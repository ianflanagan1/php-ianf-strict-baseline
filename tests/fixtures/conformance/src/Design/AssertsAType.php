<?php

declare(strict_types=1);

namespace App\Design;

/**
 * Narrows a value with assert(), which production builds skip.
 */
final class AssertsAType
{
    public function asserted(mixed $value): string
    {
        assert(is_string($value));

        return strtoupper($value);
    }
}
