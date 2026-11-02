<?php

declare(strict_types=1);

namespace App\Design;

final class MissingCase
{
    public function colour(Colour $c): string
    {
        return match ($c) {
            Colour::Red => 'red',
            Colour::Green => 'green',
        };
    }

    public function result(Result $r): string
    {
        return match (true) {
            $r instanceof Success => 'success',
        };
    }
}
