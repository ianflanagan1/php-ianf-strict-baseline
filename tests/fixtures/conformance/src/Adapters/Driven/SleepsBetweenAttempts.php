<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

/**
 * Sleeps between attempts to reach a service.
 */
final class SleepsBetweenAttempts
{
    public function fetch(): string
    {
        $body = @file_get_contents('https://example.com');
        if ($body === false) {
            sleep(1);

            return (string) @file_get_contents('https://example.com');
        }

        return $body;
    }
}
