<?php

declare(strict_types=1);

namespace App\Design;

/**
 * Uses a value of unknown type without a test.
 */
final class UsesMixed
{
    public function save(mixed $value): void
    {
        $value->save();
    }
}
