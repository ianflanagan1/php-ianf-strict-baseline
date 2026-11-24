<?php

declare(strict_types=1);

namespace App\Design;

/**
 * Converts a value of unknown type with a cast, which turns one that isn't a number into 0.
 */
final class CastsMixed
{
    public function quantity(mixed $value): int
    {
        return (int) $value;
    }
}
