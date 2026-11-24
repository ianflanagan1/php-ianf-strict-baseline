<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

use App\Core\OrderMissing;
use App\Ports\Orders;

/**
 * Throws for an expected failure and declares it, which its port doesn't.
 */
final class DeclaresWhatItThrows implements Orders
{
    /**
     * @throws OrderMissing
     */
    #[\Override]
    public function find(string $id): ?string
    {
        throw new OrderMissing($id);
    }
}
