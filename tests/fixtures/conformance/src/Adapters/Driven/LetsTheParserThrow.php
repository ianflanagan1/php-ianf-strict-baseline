<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

use App\Ports\Orders;

/**
 * Lets the exception that standard-library code throws for an expected failure, a malformed
 * document, escape through the port.
 */
final class LetsTheParserThrow implements Orders
{
    #[\Override]
    public function find(string $id): ?string
    {
        $order = json_decode($id, flags: JSON_THROW_ON_ERROR);

        return is_string($order) ? $order : null;
    }
}
