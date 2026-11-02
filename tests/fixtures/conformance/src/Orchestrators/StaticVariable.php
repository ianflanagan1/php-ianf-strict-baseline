<?php

declare(strict_types=1);

namespace App\Orchestrators;

final class StaticVariable
{
    public function count(): int
    {
        static $calls = 0;

        return ++$calls;
    }
}
