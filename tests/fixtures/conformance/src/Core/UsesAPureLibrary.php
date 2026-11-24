<?php

declare(strict_types=1);

namespace App\Core;

use Acme\Toolkit\Slug;

/**
 * Calls third-party code that reaches nothing impure.
 */
final class UsesAPureLibrary
{
    public function slug(string $title): string
    {
        return Slug::of($title);
    }
}
