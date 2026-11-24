<?php

declare(strict_types=1);

namespace App\Orchestrators;

final class StaticVariable
{
    public function count(): int
    {
        static $calls = 0;

        $calls = is_int($calls) ? $calls + 1 : 1;

        return $calls;
    }
}
