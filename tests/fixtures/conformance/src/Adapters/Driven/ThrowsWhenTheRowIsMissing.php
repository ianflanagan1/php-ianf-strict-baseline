<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

use App\Core\OrderMissing;
use App\Ports\Orders;

/**
 * Throws for an expected failure, and says nothing of it.
 */
final class ThrowsWhenTheRowIsMissing implements Orders
{
    #[\Override]
    public function find(string $id): ?string
    {
        throw new OrderMissing($id);
    }
}
