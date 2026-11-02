<?php

declare(strict_types=1);

namespace App\Orchestrators;

use App\Adapters\Driven\SystemClock;

final class ImportsAdapter
{
    public function now(): int
    {
        return (new SystemClock)->now();
    }
}
