<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

use App\Adapters\Driven\SystemClock;

final class ImportsDriven
{
    public function handle(SystemClock $clock): int
    {
        return $clock->now();
    }
}
