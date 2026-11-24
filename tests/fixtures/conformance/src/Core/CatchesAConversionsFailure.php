<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Catches the exception a standard-library conversion throws on an invalid string, and returns
 * the failure as a value.
 */
final class CatchesAConversionsFailure
{
    public function zone(string $name): ?\DateTimeZone
    {
        try {
            return new \DateTimeZone($name);
        } catch (\DateInvalidTimeZoneException) {
            return null;
        }
    }
}
