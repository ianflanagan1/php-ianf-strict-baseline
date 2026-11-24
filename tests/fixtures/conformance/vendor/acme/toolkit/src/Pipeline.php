<?php

declare(strict_types=1);

namespace Acme\Toolkit;

/**
 * Calls a function value it built itself, which the check can't show is pure.
 */
final class Pipeline
{
    public static function shout(string $text): string
    {
        $fn = static fn (string $s): string => strtoupper($s);

        return $fn($text);
    }
}
