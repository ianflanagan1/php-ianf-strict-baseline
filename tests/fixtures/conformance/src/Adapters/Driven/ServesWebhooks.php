<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

/**
 * Serves requests, which is a driving adapter's operation.
 */
final class ServesWebhooks
{
    /**
     * @return resource|false
     */
    public function listen()
    {
        return stream_socket_server('tcp://0.0.0.0:8080');
    }
}
