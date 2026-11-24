<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Builds a time from a Unix timestamp in the machine's local time zone.
 */
final class LocalTime
{
    public function day(int $timestamp): string
    {
        return date('Y-m-d', $timestamp);
    }
}
