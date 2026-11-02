<?php

declare(strict_types=1);

$container = new \DI\Container;
echo $container->get('greeter'), PHP_EOL;
