<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

/**
 * Calls out over the network through a function that reads files, which the listing records as a
 * gap.
 */
final class FetchesAPageAsAFile
{
    public function fetch(): string|false
    {
        return file_get_contents('https://example.com');
    }
}
