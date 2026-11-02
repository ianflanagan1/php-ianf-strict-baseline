<?php

declare(strict_types=1);

namespace App\Orchestrators;

final class Filesystem
{
    public function read(): string|false
    {
        return file_get_contents('f');
    }
}
