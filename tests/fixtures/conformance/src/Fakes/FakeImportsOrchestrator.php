<?php

declare(strict_types=1);

namespace App\Fakes;

use App\Orchestrators\Greeter;

/**
 * A fake that references an orchestrator, which no fake may.
 */
final class FakeImportsOrchestrator
{
    public function __construct(private readonly Greeter $greeter) {}

    public function hello(): string
    {
        return $this->greeter->hello();
    }
}
