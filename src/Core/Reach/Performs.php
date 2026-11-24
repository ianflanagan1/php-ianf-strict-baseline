<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Core\Reach;

/**
 * An impure operation: a function, class, constant, superglobal or keyword the baseline's
 * effect bans list, or state kept between calls.
 */
final readonly class Performs implements Step
{
    /**
     * @param  string  $label  the operation and what it does, such as `time(), which reads the clock`
     */
    private function __construct(public string $label)
    {
    }

    public static function operation(string $label): self
    {
        return new self($label);
    }
}
