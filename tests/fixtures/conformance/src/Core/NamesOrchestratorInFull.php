<?php

declare(strict_types=1);

namespace App\Core;

/**
 * References an orchestrator by its qualified name, with no import.
 */
final class NamesOrchestratorInFull
{
    public function greet(\App\Orchestrators\Greeter $greeter): string
    {
        return $greeter->hello();
    }
}
