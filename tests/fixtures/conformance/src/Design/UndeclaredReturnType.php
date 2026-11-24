<?php

declare(strict_types=1);

namespace App\Design;

final class UndeclaredReturnType
{
    public function upper(string $s)
    {
        return strtoupper($s);
    }
}
