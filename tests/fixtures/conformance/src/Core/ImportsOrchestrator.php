<?php

declare(strict_types=1);

namespace App\Core;

use App\Orchestrators\Greeter;

final class ImportsOrchestrator
{
    public function greet(Greeter $greeter): string
    {
        return $greeter->hello();
    }
}
