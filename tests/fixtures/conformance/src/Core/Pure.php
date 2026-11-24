<?php

declare(strict_types=1);

namespace App\Core;

final class Pure
{
    public function upper(string $s): string
    {
        return strtoupper($s);
    }
}
