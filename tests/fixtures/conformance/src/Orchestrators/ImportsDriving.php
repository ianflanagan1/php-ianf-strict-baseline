<?php

declare(strict_types=1);

namespace App\Orchestrators;

use App\Adapters\Driving\Server;

final class ImportsDriving
{
    public function greet(Server $server): string
    {
        return $server->handle();
    }
}
