<?php

declare(strict_types=1);

namespace App\Testing;

use App\Core\Pure;
use App\Fakes\FakeClock;
use App\Ports\Clock;

/**
 * Test code a project ships for the tests of projects that require it: it may use the core, ports and fakes.
 */
final class ClockAssertion
{
    public function __construct(private readonly Pure $pure) {}

    public function describe(Clock $clock, FakeClock $fake): string
    {
        return $this->pure->upper((string) ($clock->now() - $fake->now()));
    }
}
