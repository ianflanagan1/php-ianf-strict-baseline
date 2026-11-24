<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

use IanFlanagan\SomeLibrary\Adapters\Driven\SystemClock;

/**
 * A controller given a first-party dependency's clock, which stamps the request itself.
 */
final class ReadsLibraryClock
{
    public function __construct(private readonly SystemClock $clock) {}

    public function handle(): int
    {
        return $this->clock->now();
    }
}
