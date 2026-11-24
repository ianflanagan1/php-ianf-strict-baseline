<?php

declare(strict_types=1);

namespace App\Core;

use App\Testing\ClockAssertion;

/**
 * Core code that references shipped test code, which only test code may.
 */
final class ImportsTesting
{
    public function __construct(private readonly ClockAssertion $assertion) {}

    public function described(): bool
    {
        return isset($this->assertion);
    }
}
