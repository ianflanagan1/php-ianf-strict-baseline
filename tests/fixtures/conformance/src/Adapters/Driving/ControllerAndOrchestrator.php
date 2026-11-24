<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

use App\Ports\Clock;

/**
 * One file that declares a controller and the orchestrator it calls. Both take the directory's
 * role, so the orchestrator's port is reported as a driving adapter's.
 */
final class TimeController
{
    public function __construct(private readonly TellTime $tellTime) {}

    public function handle(): int
    {
        return $this->tellTime->now();
    }
}

final class TellTime
{
    public function __construct(private readonly Clock $clock) {}

    public function now(): int
    {
        return $this->clock->now();
    }
}
