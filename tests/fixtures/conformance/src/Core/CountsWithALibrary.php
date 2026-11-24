<?php

declare(strict_types=1);

namespace App\Core;

use function Acme\Toolkit\counter;

/**
 * Calls a third-party function that keeps a global variable.
 */
final class CountsWithALibrary
{
    public function next(): int
    {
        return counter();
    }
}
