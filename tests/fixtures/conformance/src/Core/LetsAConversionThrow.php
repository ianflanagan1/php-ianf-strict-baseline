<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Calls a standard-library conversion that throws on an invalid string, and lets the exception
 * escape.
 */
final class LetsAConversionThrow
{
    public function zone(string $name): \DateTimeZone
    {
        return new \DateTimeZone($name);
    }
}
