<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Adapters\Driving\Console;

use IanFlanagan\PhpIanfStrictBaseline\Orchestrators\Verify;

/**
 * `php-ianf-strict-baseline verify`: prints what the project must change to follow the baseline.
 */
final readonly class VerifyCommand
{
    public function __construct(private Verify $verify, private string $root)
    {
    }

    /** Runs the command, and returns its exit code. */
    public function run(): int
    {
        $problems = $this->verify->problems();
        if ($problems === []) {
            fwrite(STDOUT, "php-ianf-strict-baseline: {$this->root} follows the baseline.\n");

            return 0;
        }
        $list = implode('', array_map(static fn (string $problem): string => "  - {$problem}\n", $problems));
        fwrite(STDERR, "php-ianf-strict-baseline: {$this->root} departs from the baseline:\n{$list}");

        return 1;
    }
}
