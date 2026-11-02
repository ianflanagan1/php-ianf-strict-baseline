<?php

declare(strict_types=1);

namespace App\Ports;

use App\Greeter;

interface ImportsOrchestrator
{
    public function greet(Greeter $greeter): string;
}
