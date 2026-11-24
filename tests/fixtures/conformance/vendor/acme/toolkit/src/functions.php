<?php

declare(strict_types=1);

namespace Acme\Toolkit;

// Only declarations: loading this file does nothing.

/**
 * Reads the clock two calls deep: stamp() calls Clock::seconds(), which calls time().
 */
function stamp(string $label): string
{
    return $label.'@'.Clock::seconds();
}

/**
 * Keeps a count in a global variable.
 */
function counter(): int
{
    global $acmeCounter;
    $acmeCounter = (int) $acmeCounter + 1;

    return $acmeCounter;
}

/**
 * Calls the function it was passed, so what it reaches is what its caller passes.
 */
function apply(callable $fn, string $value): string
{
    return $fn($value);
}
