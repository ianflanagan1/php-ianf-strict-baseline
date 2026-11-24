<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Orchestrators;

use IanFlanagan\PhpIanfStrictBaseline\Core\ProjectProblems;
use IanFlanagan\PhpIanfStrictBaseline\Ports\ProjectFiles;

/**
 * Checks that a project still follows the baseline.
 */
final readonly class Verify
{
    public function __construct(private ProjectFiles $files)
    {
    }

    /**
     * @return list<string>
     */
    public function problems(): array
    {
        return ProjectProblems::find($this->files->gitignore());
    }
}
