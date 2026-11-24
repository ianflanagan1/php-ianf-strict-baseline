<?php

declare(strict_types=1);

namespace App\Core;

use Acme\Toolkit\Pipeline;

/**
 * Calls third-party code that calls a function value it built itself.
 */
final class ShoutsWithALibrary
{
    public function shout(string $text): string
    {
        return Pipeline::shout($text);
    }
}
