<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

use App\Core\Pure;

/**
 * Has PDO set a core type's properties through a fetch mode.
 */
final class FetchesRowsAsAClass
{
    /**
     * @return array<mixed>
     */
    public function asClass(\PDOStatement $rows): array
    {
        return $rows->fetchAll(\PDO::FETCH_CLASS, Pure::class);
    }

    public function into(\PDOStatement $rows, Pure $pure): bool
    {
        return $rows->setFetchMode(\PDO::FETCH_INTO, $pure);
    }
}
