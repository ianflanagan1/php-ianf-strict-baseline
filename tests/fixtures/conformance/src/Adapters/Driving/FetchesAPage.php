<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

/**
 * Calls out over the network, which is a driven adapter's operation.
 */
final class FetchesAPage
{
    public function fetch(): \CurlHandle|false
    {
        return curl_init('https://example.com');
    }
}
