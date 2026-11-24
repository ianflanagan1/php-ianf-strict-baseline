<?php

declare(strict_types=1);

namespace App\Core;

use Acme\Toolkit\Report;

/**
 * Calls third-party code that calls a method on an interface value it builds itself.
 */
final class RendersWithALibrary
{
    public function render(string $text): string
    {
        return Report::render($text);
    }
}
