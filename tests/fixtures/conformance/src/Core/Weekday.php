<?php

declare(strict_types=1);

namespace App\Core;

/**
 * An enum whose method reads the current time.
 */
enum Weekday
{
    case Saturday;
    case Sunday;

    public function secondsSinceEpoch(): int
    {
        return time();
    }
}
