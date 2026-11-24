<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

/**
 * Matches an error type the standard library declares, which has no role.
 */
final class CatchesStandardException
{
    public function decode(string $body): mixed
    {
        try {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }
}
