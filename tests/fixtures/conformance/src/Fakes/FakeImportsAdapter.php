<?php

declare(strict_types=1);

namespace App\Fakes;

use App\Adapters\Driven\SystemClock;

/**
 * A fake that wraps its port's adapter, which no fake may.
 */
final class FakeImportsAdapter
{
    public function __construct(private readonly SystemClock $clock) {}

    public function now(): int
    {
        return $this->clock->now();
    }
}
