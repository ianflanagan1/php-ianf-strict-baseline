<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

use App\Adapters\Driving\Server;

final class ImportsDriving
{
    public function greet(Server $server): string
    {
        return $server->handle();
    }
}
