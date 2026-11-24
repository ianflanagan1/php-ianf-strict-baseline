<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

use App\Ports\Orders;

/**
 * Catches the exception thrown for an expected failure at the call, and returns the failure
 * through the port as a value.
 */
final class ConvertsTheFailure implements Orders
{
    #[\Override]
    public function find(string $id): ?string
    {
        try {
            $order = json_decode($id, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_string($order) ? $order : null;
    }
}
