<?php

declare(strict_types=1);

namespace App\Design;

final class EnumDefault
{
    public function colour(Colour $c): string
    {
        return match ($c) {
            Colour::Red => 'red',
            Colour::Green => 'green',
            default => 'other',
        };
    }
}
