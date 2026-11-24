<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

/**
 * A driving adapter that keeps to its role's operations: it serves requests.
 */
final class AnswersRequests
{
    public function name(): string
    {
        header('Content-Type: text/plain');
        $name = $_GET['name'] ?? '';

        return is_string($name) ? $name : '';
    }

    /**
     * @return resource|false
     */
    public function listen()
    {
        return stream_socket_server('tcp://0.0.0.0:8080');
    }
}
