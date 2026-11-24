<?php

declare(strict_types=1);

namespace App\Design;

final class BuildsWithoutAConstructor
{
    public function built(): object
    {
        return (new \ReflectionClass(Success::class))->newInstanceWithoutConstructor();
    }

    public function revived(string $data): mixed
    {
        return unserialize($data);
    }

    public function revivedAsAnyClass(string $data): mixed
    {
        return unserialize($data, ['allowed_classes' => true]);
    }
}
