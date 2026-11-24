<?php

declare(strict_types=1);

namespace App\Design;

/**
 * Reads an object's private properties by casting it to an array, which the listing records as a
 * gap.
 */
final class ReadsPrivatePropertiesByCast
{
    /**
     * @return array<mixed>
     */
    public function cast(object $order): array
    {
        return (array) $order;
    }
}
