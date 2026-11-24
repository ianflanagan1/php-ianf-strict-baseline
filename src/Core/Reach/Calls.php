<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Core\Reach;

/**
 * A call to a function or a method whose code is read and followed.
 */
final readonly class Calls implements Step
{
    /**
     * @param  string  $target  the callee's key in the graph of what each piece of code does
     * @param  string  $label  the callee as a message names it, such as `Acme\Clock::seconds()`
     * @param  string  $file  the file that holds the callee's code
     */
    private function __construct(public string $target, public string $label, public string $file)
    {
    }

    public static function to(string $target, string $label, string $file): self
    {
        return new self($target, $label, $file);
    }
}
