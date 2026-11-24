<?php

declare(strict_types=1);

namespace App\Design;

/**
 * Tests a value of unknown type before using it.
 */
final class TestsMixed
{
    public function length(mixed $value): int
    {
        return is_string($value) ? strlen($value) : 0;
    }
}
