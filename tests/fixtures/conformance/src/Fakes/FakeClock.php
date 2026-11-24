<?php

declare(strict_types=1);

namespace App\Fakes;

use App\Core\Pure;
use App\Ports\Clock;

/**
 * A fake: it implements its port and uses the core, which a fake may.
 */
final class FakeClock implements Clock
{
    public function __construct(private readonly int $now, private readonly Pure $pure) {}

    #[\Override]
    public function now(): int
    {
        return $this->now;
    }

    public function label(): string
    {
        return $this->pure->upper('fixed');
    }
}
