<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

use App\Ports\Orders;

/**
 * Throws a RuntimeException for an expected failure, and lets its driver's exception for a
 * unique-constraint violation, a RuntimeException too, escape. Both are unchecked, which the
 * listing records as a gap.
 */
final class ThrowsAnUncheckedException implements Orders
{
    public function __construct(private readonly \PDO $database) {}

    #[\Override]
    public function find(string $id): ?string
    {
        throw new RowNotFound($id);
    }

    public function save(string $id): void
    {
        $this->database->prepare('INSERT INTO orders (id) VALUES (?)')->execute([$id]);
    }
}
