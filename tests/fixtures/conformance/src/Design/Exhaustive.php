<?php

declare(strict_types=1);

namespace App\Design;

final class Exhaustive
{
    public function colour(Colour $c): string
    {
        return match ($c) {
            Colour::Red, Colour::Green => 'warm or green',
            Colour::Blue => 'blue',
        };
    }

    public function result(Result $r): string
    {
        return match (true) {
            $r instanceof Success => 'success',
            $r instanceof Failure => 'failure',
        };
    }

    public function open(Shape $s, int $n): string
    {
        $kind = match (true) {
            $s instanceof \Countable => 'countable',
            default => 'shape',
        };

        return $kind.match ($n) {
            0 => 'none',
            default => 'some',
        };
    }
}
