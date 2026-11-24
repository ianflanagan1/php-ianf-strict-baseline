<?php

declare(strict_types=1);

namespace App\Design;

/**
 * Field initialisers that build a Closure, which PHP compiles from 8.5, and which the listing
 * records as a gap.
 */
final class InitialisesAFieldWithAClosure
{
    public \Closure $one = static function (): int {
        return 1;
    };

    public \Closure $trim = trim(...);
}
