<?php

declare(strict_types=1);

namespace App\Orchestrators;

final class Clock
{
    public function now(): int
    {
        return time();
    }

    public function today(): \DateTimeImmutable
    {
        return new \DateTimeImmutable;
    }
}
