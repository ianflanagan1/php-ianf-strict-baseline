<?php

declare(strict_types=1);

namespace App\Design;

/**
 * Implements an interface's method without #[\Override], so nothing reports the method when the
 * interface drops or renames it.
 */
final class OverridesWithoutTheAttribute implements \Countable
{
    public function count(): int
    {
        return 0;
    }
}
