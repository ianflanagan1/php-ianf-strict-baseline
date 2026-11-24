<?php

declare(strict_types=1);

namespace App\Core;

use Acme\Toolkit\Memo;

/**
 * Calls a third-party class that caches in a static property.
 */
final class MemoisesWithALibrary
{
    public function area(int $side): int
    {
        return Memo::square($side);
    }
}
