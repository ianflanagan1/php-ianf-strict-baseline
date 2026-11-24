<?php

declare(strict_types=1);

namespace App\Design;

/**
 * Narrows a value of unknown type with annotations nothing tests, which the listing records as a
 * gap.
 */
final class ClaimsATypeUntested
{
    public function annotated(mixed $value): string
    {
        /** @var string $name */
        $name = $value;

        return strtoupper($name);
    }

    /**
     * @phpstan-assert-if-true string $value
     */
    public function isName(mixed $value): bool
    {
        return true;
    }
}
