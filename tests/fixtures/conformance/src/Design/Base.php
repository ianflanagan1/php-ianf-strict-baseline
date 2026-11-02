<?php

declare(strict_types=1);

namespace App\Design;

class Base
{
    public function greet(): string
    {
        return 'hello';
    }
}

abstract class AbstractBase
{
    abstract public function name(): string;
}
