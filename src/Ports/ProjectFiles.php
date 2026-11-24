<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Ports;

/**
 * The files of the project being verified.
 */
interface ProjectFiles
{
    /** The project's .gitignore, or null when it has none. */
    public function gitignore(): ?string;
}
