<?php

declare(strict_types=1);

namespace App\Ports;

/**
 * A port that returns its expected failure: null when there is no such order.
 */
interface Orders
{
    public function find(string $id): ?string;
}
