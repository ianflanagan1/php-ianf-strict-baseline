<?php

declare(strict_types=1);

namespace Acme\Toolkit;

interface Writer
{
    public function write(string $text): string;
}
