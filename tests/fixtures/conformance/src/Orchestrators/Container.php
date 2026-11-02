<?php

declare(strict_types=1);

namespace App\Orchestrators;

use Psr\Container\ContainerInterface;

final class Container
{
    public function greeter(ContainerInterface $c): mixed
    {
        return $c->get('greeter');
    }
}
