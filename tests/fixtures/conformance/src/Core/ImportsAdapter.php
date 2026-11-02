<?php

declare(strict_types=1);

namespace App\Core;

use App\Adapters\Driven\SystemClock;

final class ImportsAdapter
{
    public function year(SystemClock $clock): int
    {
        return $clock->now();
    }
}
