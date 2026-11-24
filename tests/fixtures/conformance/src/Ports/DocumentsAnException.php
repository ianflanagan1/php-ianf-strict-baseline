<?php

declare(strict_types=1);

namespace App\Ports;

use App\Core\OrderMissing;

/**
 * A port whose documentation names an exception for an expected failure, which the listing records
 * as a gap.
 */
interface DocumentsAnException
{
    /**
     * @throws OrderMissing when there is no such order
     */
    public function find(string $id): string;
}
