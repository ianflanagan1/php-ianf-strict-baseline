<?php

declare(strict_types=1);

namespace App\Ports;

interface Clock
{
    public function now(): int;
}
