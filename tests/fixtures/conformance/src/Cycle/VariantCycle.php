<?php

declare(strict_types=1);

namespace App\Cycle;

/**
 * A variant of a sealed union references a class that references the union.
 *
 * @phpstan-sealed Paid|Unpaid
 */
interface Payment {}

final class Paid implements Payment
{
    public function __construct(public readonly Receipt $receipt) {}
}

final class Unpaid implements Payment {}

final class Receipt
{
    public function __construct(public readonly Payment $payment) {}
}
