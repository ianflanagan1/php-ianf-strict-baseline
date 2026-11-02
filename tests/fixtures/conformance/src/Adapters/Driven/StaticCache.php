<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

final class StaticCache
{
    private static ?string $home = null;

    public function home(): string
    {
        return self::$home ??= (string) realpath('.');
    }
}
