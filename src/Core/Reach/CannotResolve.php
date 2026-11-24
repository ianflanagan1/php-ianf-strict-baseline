<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Core\Reach;

/**
 * A call whose callee the check can't tell, and so can't show is pure, such as a call through a
 * function value the code built itself.
 */
final readonly class CannotResolve implements Step
{
    /**
     * @param  string  $label  the call and why it can't be followed
     */
    private function __construct(public string $label)
    {
    }

    public static function call(string $label): self
    {
        return new self($label);
    }
}
