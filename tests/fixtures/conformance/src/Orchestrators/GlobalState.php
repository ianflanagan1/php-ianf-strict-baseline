<?php

declare(strict_types=1);

namespace App\Orchestrators;

final class GlobalState
{
    public function count(): int
    {
        global $count;

        return is_int($count) ? $count : 0;
    }
}
