<?php

declare(strict_types=1);

namespace App\Design;

final class SealedDefault
{
    public function result(Result $r): string
    {
        return match (true) {
            $r instanceof Success => 'success',
            $r instanceof Failure => 'failure',
            default => 'neither',
        };
    }
}
