<?php

declare(strict_types=1);

namespace App\Design;

/**
 * Unserialises with no class allowed, so the data can build no object.
 */
final class RevivesPlainData
{
    public function revived(string $data): mixed
    {
        return unserialize($data, ['allowed_classes' => false]);
    }
}
