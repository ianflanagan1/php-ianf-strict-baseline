<?php

declare(strict_types=1);

use App\Adapters\Driven\SystemClock;
use App\Adapters\Driving\Server;
use App\Greeter;

echo (new Server(new Greeter))->handle(), (new SystemClock)->now(), getenv('HOME'), PHP_EOL;
