<?php

declare(strict_types=1);

// Calls out over the network, which is a driven adapter's operation.
$handle = curl_init('https://example.com/health');
echo $handle === false ? 'down' : 'up', PHP_EOL;
