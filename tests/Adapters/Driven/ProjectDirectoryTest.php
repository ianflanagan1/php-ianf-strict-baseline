<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Tests\Adapters\Driven;

use IanFlanagan\PhpIanfStrictBaseline\Adapters\Driven\ProjectDirectory;
use Override;
use PHPUnit\Framework\TestCase;

final class ProjectDirectoryTest extends TestCase
{
    private string $root;

    #[Override]
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/php-ianf-strict-baseline-'.bin2hex(random_bytes(8));
        mkdir($this->root);
    }

    #[Override]
    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->root.'/{,.}[!.]*', GLOB_BRACE) ?: []);
        rmdir($this->root);
    }

    public function test_it_reads_the_gitignore_at_the_root(): void
    {
        file_put_contents($this->root.'/.gitignore', "/cache/\n");

        $this->assertSame("/cache/\n", new ProjectDirectory($this->root)->gitignore());
    }

    public function test_a_missing_gitignore_is_null(): void
    {
        $this->assertNull(new ProjectDirectory($this->root)->gitignore());
    }
}
