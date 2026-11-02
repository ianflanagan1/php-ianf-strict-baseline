<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

use App\Orchestrators\Greeter;

/**
 * A driving adapter that keeps to its role: it answers a request by calling an orchestrator.
 */
final class Server
{
    public function __construct(private readonly Greeter $greeter) {}

    public function handle(): string
    {
        return $this->greeter->hello();
    }
}
