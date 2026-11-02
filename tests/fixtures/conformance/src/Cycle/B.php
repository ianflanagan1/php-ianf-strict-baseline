<?php

declare(strict_types=1);

namespace App\Cycle;

final class B
{
    public function a(): A
    {
        return new A;
    }
}
