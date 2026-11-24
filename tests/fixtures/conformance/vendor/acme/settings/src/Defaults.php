<?php

declare(strict_types=1);

namespace Acme\Settings;

/**
 * Pure itself: what is impure is the package's load-time code.
 */
final class Defaults
{
    public static function currency(): string
    {
        return 'EUR';
    }
}
