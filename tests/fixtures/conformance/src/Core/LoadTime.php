<?php

declare(strict_types=1);

namespace App\Core;

// Load-time code: it runs when the file is loaded, before anything in it is called.
$home = getenv('HOME');

final class LoadTime
{
    public function upper(string $s): string
    {
        return strtoupper($s);
    }
}
