<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

/**
 * A driven adapter that keeps to its role's operations: it calls out, reads a database and reads
 * files.
 */
final class CallsOut
{
    public function fetch(): \CurlHandle|false
    {
        return curl_init('https://example.com');
    }

    public function connect(): \PDO
    {
        return new \PDO('sqlite::memory:');
    }

    public function read(): string|false
    {
        return file_get_contents('orders.json');
    }
}
