<?php

declare(strict_types=1);

namespace App\Core;

use function Acme\Toolkit\stamp;

/**
 * Calls a third-party function that reads the clock two calls deep.
 */
final class StampsWithALibrary
{
    public function label(string $name): string
    {
        return stamp($name);
    }
}
