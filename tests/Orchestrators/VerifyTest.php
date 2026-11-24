<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Tests\Orchestrators;

use IanFlanagan\PhpIanfStrictBaseline\Orchestrators\Verify;
use IanFlanagan\PhpIanfStrictBaseline\Ports\ProjectFiles;
use Override;
use PHPUnit\Framework\TestCase;

final class VerifyTest extends TestCase
{
    public function test_it_reports_the_problems_in_the_files_it_reads(): void
    {
        $files = new class () implements ProjectFiles {
            #[Override]
            public function gitignore(): string
            {
                return "/vendor/\n";
            }
        };

        $this->assertSame(
            ['.gitignore: add the line `/cache/`, for the directory tools keep their caches in.'],
            new Verify($files)->problems(),
        );
    }
}
