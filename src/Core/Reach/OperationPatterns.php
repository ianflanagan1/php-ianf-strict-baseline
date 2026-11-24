<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Core\Reach;

/**
 * The names one entry of an effect ban lists, as fnmatch() patterns, and what they do.
 */
final readonly class OperationPatterns
{
    /**
     * @param  list<string>  $patterns  lowercase, without a leading backslash or a trailing ()
     * @param  list<string>  $excludes  the same, for names the patterns match that the entry leaves out
     * @param  string  $does  what the operation does, such as `reads the clock`, or '' when the entry has no message
     */
    private function __construct(public array $patterns, public array $excludes, public string $does)
    {
    }

    /**
     * @param  list<string>  $patterns
     * @param  list<string>  $excludes
     */
    public static function of(array $patterns, array $excludes, string $does): self
    {
        return new self($patterns, $excludes, $does);
    }
}
