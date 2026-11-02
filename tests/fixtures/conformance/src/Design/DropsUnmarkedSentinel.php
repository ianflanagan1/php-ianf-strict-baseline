<?php

declare(strict_types=1);

namespace App\Design;

final class DropsUnmarkedSentinel
{
    public function drop(string $s): void
    {
        $found = strpos($s, 'x');
    }
}
