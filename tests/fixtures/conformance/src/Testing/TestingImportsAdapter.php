<?php

declare(strict_types=1);

namespace App\Testing;

use App\Adapters\Driven\SystemClock;

/**
 * Shipped test code that references a driven adapter, which it may not.
 */
final class TestingImportsAdapter
{
    public function __construct(private readonly SystemClock $clock) {}

    public function now(): int
    {
        return $this->clock->now();
    }
}
