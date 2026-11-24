<?php

declare(strict_types=1);

namespace App\Design;

/**
 * Calls and builds by names held in values, in the ways no function's name gives away, which the
 * listing records as a gap.
 */
final class CallsByComputedName
{
    /**
     * @param  callable-string  $name
     */
    public function called(string $name): mixed
    {
        return $name();
    }

    /**
     * @param  class-string  $class
     */
    public function built(string $class): object
    {
        return new $class;
    }

    public function counted(\Countable $items, string $method): mixed
    {
        return $items->$method();
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    public function trimmed(array $lines): array
    {
        return array_map('trim', $lines);
    }
}
