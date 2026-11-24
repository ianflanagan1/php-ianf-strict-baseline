<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lets the exception a standard-library conversion throws on an invalid string escape, and
 * declares it, which the listing records as a gap.
 */
final class DeclaresAConversionsException
{
    /**
     * @throws \DateInvalidTimeZoneException
     */
    public function zone(string $name): \DateTimeZone
    {
        return new \DateTimeZone($name);
    }
}
