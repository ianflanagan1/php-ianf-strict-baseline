<?php

declare(strict_types=1);

namespace App\Design;

function greet(string $name = 'world'): string
{
    return "hello {$name}";
}

final class Defaults
{
    public function __construct(private string $greeting = 'hello') {}

    public function greet(string $name = 'world'): string
    {
        return "{$this->greeting} {$name}";
    }
}
