<?php

declare(strict_types=1);

namespace App\Orchestrators;

use App\Fakes\FakeClock;

/**
 * An orchestrator that references a fake, which only test code may.
 */
final class UsesFake
{
    public function __construct(private readonly FakeClock $clock) {}

    public function now(): int
    {
        return $this->clock->now();
    }
}
