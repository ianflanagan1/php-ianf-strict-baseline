<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Tests\Core;

use IanFlanagan\PhpIanfStrictBaseline\Core\ProjectProblems;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProjectProblemsTest extends TestCase
{
    public function test_a_project_that_ignores_the_cache_directory_has_no_problems(): void
    {
        $this->assertSame([], ProjectProblems::find("/vendor/\n/cache/\n"));
    }

    public function test_a_project_without_a_gitignore_is_asked_for_one(): void
    {
        $this->assertSame(
            ['No .gitignore: add one with the line `/cache/`, for the directory tools keep their caches in.'],
            ProjectProblems::find(null),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function gitignoresWithoutTheCacheLine(): iterable
    {
        yield 'no line for it' => ["/vendor/\n"];
        yield 'ignored at any depth' => ["cache/\n"];
        yield 'only a file of that name' => ["/cache\n"];
        yield 'commented out' => ["# /cache/\n"];
    }

    #[DataProvider('gitignoresWithoutTheCacheLine')]
    public function test_a_gitignore_without_the_cache_line_is_reported(string $gitignore): void
    {
        $this->assertSame(
            ['.gitignore: add the line `/cache/`, for the directory tools keep their caches in.'],
            ProjectProblems::find($gitignore),
        );
    }

    public function test_the_cache_line_may_end_with_spaces_or_a_carriage_return(): void
    {
        $this->assertSame([], ProjectProblems::find("/cache/  \r\n"));
    }
}
