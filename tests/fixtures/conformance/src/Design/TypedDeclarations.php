<?php

declare(strict_types=1);

namespace App\Design;

/**
 * Every declaration has a type PHPStan knows: declared, or, for a callback's parameter, taken
 * from the function type it is passed as.
 */
final class TypedDeclarations
{
    /**
     * @param  list<string>  $names
     */
    public function __construct(public readonly array $names) {}

    /**
     * @return list<string>
     */
    public function upper(): array
    {
        return array_map(static fn ($name) => strtoupper($name), $this->names);
    }
}
