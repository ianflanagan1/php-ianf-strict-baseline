<?php

declare(strict_types=1);

namespace App\Design;

#[\NoDiscard]
function validate(string $s): bool
{
    return $s !== '';
}

final class DropsResult
{
    #[\NoDiscard]
    public function parse(string $s): Result
    {
        return $s === '' ? new Failure : new Success;
    }

    public function drop(string $s): void
    {
        validate($s);
        $this->parse($s);
    }
}
