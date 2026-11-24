<?php

declare(strict_types=1);

namespace Acme\Toolkit;

final class UpperWriter implements Writer
{
    public function write(string $text): string
    {
        return strtoupper($text);
    }
}
