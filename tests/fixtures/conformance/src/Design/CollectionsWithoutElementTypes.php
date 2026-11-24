<?php

declare(strict_types=1);

namespace App\Design;

final class CollectionsWithoutElementTypes
{
    public function __construct(public readonly iterable $rows) {}

    public function count(array $items): int
    {
        return count($items);
    }
}
