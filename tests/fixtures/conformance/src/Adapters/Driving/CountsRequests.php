<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

/**
 * Keeps global state, which no role's code does.
 */
final class CountsRequests
{
    public function count(): int
    {
        global $count;

        return is_int($count) ? $count : 0;
    }
}
