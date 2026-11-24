<?php

declare(strict_types=1);

namespace App\Core;

use Acme\Settings\Defaults;

/**
 * Calls a pure third-party function in a package whose Composer autoload files entry reads the
 * environment when it is loaded.
 */
final class LoadsALibrary
{
    public function currency(): string
    {
        return Defaults::currency();
    }
}
