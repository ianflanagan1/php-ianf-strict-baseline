<?php

declare(strict_types=1);

namespace App\Ports;

use App\Adapters\Driven\SystemClock;

interface ImportsAdapter
{
    public function clock(): SystemClock;
}
