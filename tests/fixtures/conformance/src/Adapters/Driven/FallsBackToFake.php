<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

use App\Fakes\FakeClock;

/**
 * A driven adapter that falls back to its port's fake, which only test code may reference.
 */
final class FallsBackToFake
{
    public function __construct(private readonly FakeClock $fallback) {}

    public function now(): int
    {
        return $this->fallback->now();
    }
}
