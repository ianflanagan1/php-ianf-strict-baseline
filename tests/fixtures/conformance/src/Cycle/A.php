<?php

declare(strict_types=1);

namespace App\Cycle;

final class A
{
    public function b(): B
    {
        return new B;
    }
}
