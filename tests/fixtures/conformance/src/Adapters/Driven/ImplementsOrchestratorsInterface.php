<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

use App\Orchestrators\Stock;

final class ImplementsOrchestratorsInterface implements Stock
{
    #[\Override]
    public function level(): int
    {
        return (int) file_get_contents('stock');
    }
}
