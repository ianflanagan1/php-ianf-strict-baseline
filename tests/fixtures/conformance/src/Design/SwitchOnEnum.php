<?php

declare(strict_types=1);

namespace App\Design;

final class SwitchOnEnum
{
    public function colour(Colour $c): string
    {
        switch ($c) {
            case Colour::Red:
                return 'red';
            default:
                return 'other';
        }
    }
}
