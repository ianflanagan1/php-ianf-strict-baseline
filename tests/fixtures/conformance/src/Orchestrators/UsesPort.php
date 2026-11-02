<?php

declare(strict_types=1);

namespace App\Orchestrators;

use App\Ports\Clock;

final class UsesPort
{
    public function __construct(private readonly Clock $clock) {}

    public function now(): int
    {
        return $this->clock->now();
    }
}
