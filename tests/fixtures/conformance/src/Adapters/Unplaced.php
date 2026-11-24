<?php

declare(strict_types=1);

namespace App\Adapters;

/**
 * Under src/Adapters/ but in neither Driving/ nor Driven/, so it has no role, and the clock it
 * reads is reported.
 */
final class Unplaced
{
    public function now(): int
    {
        return time();
    }
}
