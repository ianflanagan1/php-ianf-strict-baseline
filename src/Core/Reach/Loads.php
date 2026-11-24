<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Core\Reach;

/**
 * Loading a file, whose load-time code then runs: a class's file when the class is first used, or
 * a file a Composer autoload files entry names.
 */
final readonly class Loads implements Step
{
    /**
     * @param  string  $file  the file, which is also its load-time code's key in the graph
     * @param  string  $label  the load as a message names it, such as `loads vendor/acme/x/bootstrap.php`
     */
    private function __construct(public string $file, public string $label)
    {
    }

    public static function file(string $file, string $label): self
    {
        return new self($file, $label);
    }
}
