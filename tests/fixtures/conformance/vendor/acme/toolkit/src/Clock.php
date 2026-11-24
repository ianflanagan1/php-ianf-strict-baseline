<?php

declare(strict_types=1);

namespace Acme\Toolkit;

final class Clock
{
    public static function seconds(): int
    {
        return time();
    }
}
