<?php

declare(strict_types=1);

namespace App\Core;

final class Sleeps
{
    public function pause(): void
    {
        usleep(1000);
    }
}
