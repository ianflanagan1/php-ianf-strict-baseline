<?php

declare(strict_types=1);

namespace App\Core;

use function Acme\Toolkit\apply;

/**
 * Passes a pure function to third-party code that calls the function it was passed, so the
 * core's own argument decides what that call reaches.
 */
final class AppliesWithALibrary
{
    public function upper(string $text): string
    {
        return apply(strtoupper(...), $text);
    }
}
