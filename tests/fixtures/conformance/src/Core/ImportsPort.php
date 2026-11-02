<?php

declare(strict_types=1);

namespace App\Core;

use App\Ports\Clock;

final class ImportsPort
{
    public function now(Clock $clock): int
    {
        return $clock->now();
    }
}
