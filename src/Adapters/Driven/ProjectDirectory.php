<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Adapters\Driven;

use IanFlanagan\PhpIanfStrictBaseline\Ports\ProjectFiles;
use Override;
use RuntimeException;

/**
 * The files of the project in a directory on disk.
 */
final readonly class ProjectDirectory implements ProjectFiles
{
    public function __construct(private string $root)
    {
    }

    #[Override]
    public function gitignore(): ?string
    {
        $file = $this->root.'/.gitignore';
        if (!is_file($file)) {
            return null;
        }
        $content = file_get_contents($file);
        if ($content === false) {
            throw new RuntimeException("Can't read {$file}");
        }

        return $content;
    }
}
