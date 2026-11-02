<?php

declare(strict_types=1);

namespace App\Orchestrators;

final class Logging
{
    public function log(): bool
    {
        return error_log('hello');
    }
}
