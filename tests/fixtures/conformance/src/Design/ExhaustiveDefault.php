<?php

declare(strict_types=1);

namespace App\Design;

/**
 * Matches that name every case and still have a default arm.
 */
final class ExhaustiveDefault
{
    public function colour(Colour $c): string
    {
        return match ($c) {
            Colour::Red => 'red',
            Colour::Green => 'green',
            Colour::Blue => 'blue',
            default => 'none',
        };
    }

    public function result(Result $r): string
    {
        return match (true) {
            $r instanceof Success => 'success',
            $r instanceof Failure => 'failure',
            default => throw new \LogicException('unreachable'),
        };
    }
}
