<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

/**
 * Reads a database, which is a driven adapter's operation.
 */
final class LooksUpASession
{
    public function connect(): object
    {
        return new \PDO('sqlite::memory:');
    }
}
