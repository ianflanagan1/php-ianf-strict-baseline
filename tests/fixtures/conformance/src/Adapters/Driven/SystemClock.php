<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

use App\Ports\Clock;

final class SystemClock implements Clock
{
    #[\Override]
    public function now(): int
    {
        return time();
    }
}
