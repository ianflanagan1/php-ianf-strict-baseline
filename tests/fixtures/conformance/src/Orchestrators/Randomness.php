<?php

declare(strict_types=1);

namespace App\Orchestrators;

final class Randomness
{
    public function roll(): int
    {
        return random_int(1, 6);
    }
}
