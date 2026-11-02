<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

use App\Greeter;

final class ImportsOrchestrator
{
    public function greet(Greeter $greeter): string
    {
        return $greeter->hello();
    }
}
