<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

use App\Ports\Clock;

final class ImportsPort
{
    public function handle(Clock $clock): int
    {
        return $clock->now();
    }
}
