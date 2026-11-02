<?php

declare(strict_types=1);

namespace App\Core;

final class StaticProperty
{
    private static int $calls = 0;

    public function count(): int
    {
        return ++self::$calls;
    }
}
