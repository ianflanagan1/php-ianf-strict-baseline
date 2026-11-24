<?php

declare(strict_types=1);

namespace Acme\Toolkit;

/**
 * Caches what it computes in a static property, which keeps state between calls.
 */
final class Memo
{
    /** @var array<int, int> */
    private static array $squares = [];

    public static function square(int $n): int
    {
        return self::$squares[$n] ??= $n * $n;
    }
}
