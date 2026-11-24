<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

/**
 * Reads the clock and a random number in a driving adapter, which the listing records as a gap:
 * both are reserved for system adapters that PHP doesn't have yet.
 */
final class ReadsTheClock
{
    public function now(): int
    {
        return time();
    }

    public function token(): int
    {
        return mt_rand();
    }
}
