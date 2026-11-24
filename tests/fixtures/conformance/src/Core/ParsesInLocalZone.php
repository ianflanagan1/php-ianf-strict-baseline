<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Reads the machine's local time zone through functions the bans don't list, which the listing
 * records as a gap.
 */
final class ParsesInLocalZone
{
    public function parse(string $date): \DateTimeImmutable|false
    {
        return \DateTimeImmutable::createFromFormat('Y-m-d H:i', $date);
    }

    public function hour(int $timestamp): int
    {
        return getdate($timestamp)['hours'];
    }
}
