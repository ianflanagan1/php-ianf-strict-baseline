<?php

declare(strict_types=1);

namespace App\Design;

final class RunsCodeFromText
{
    public function run(string $code): mixed
    {
        return eval($code);
    }
}
