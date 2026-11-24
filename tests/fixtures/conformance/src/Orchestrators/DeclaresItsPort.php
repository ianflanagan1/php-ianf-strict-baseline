<?php

declare(strict_types=1);

namespace App\Orchestrators;

/**
 * An interface an orchestrator takes, declared in the orchestrator's own file.
 */
interface Stock
{
    public function level(): int;
}

final class CheckStock
{
    public function __construct(private readonly Stock $stock) {}

    public function level(): int
    {
        return $this->stock->level();
    }
}
