<?php

declare(strict_types=1);

namespace App\Orchestrators;

use Psr\Container\ContainerInterface;

final class Container
{
    public function hasGreeter(ContainerInterface $c): bool
    {
        return $c->has('greeter');
    }
}
