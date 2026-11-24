<?php

declare(strict_types=1);

// Serves requests itself, which is a driving adapter's operation.
$server = stream_socket_server('tcp://0.0.0.0:8080');
echo $server === false ? 'failed' : 'listening', PHP_EOL;
