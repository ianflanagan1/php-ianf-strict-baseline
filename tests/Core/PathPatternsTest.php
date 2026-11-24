<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Tests\Core;

use IanFlanagan\PhpIanfStrictBaseline\Core\PathPatterns;
use PHPUnit\Framework\TestCase;

final class PathPatternsTest extends TestCase
{
    public function test_a_file_matches_a_pattern_from_the_root(): void
    {
        $this->assertTrue(PathPatterns::matches(['src/Adapters/*'], '/project', '/project/src/Adapters/Db/Store.php'));
    }

    public function test_a_file_matching_no_pattern_does_not_match(): void
    {
        $this->assertFalse(PathPatterns::matches(['src/Adapters/*', 'bin/*'], '/project', '/project/src/Core/Price.php'));
    }

    public function test_a_root_with_a_trailing_slash_is_the_same_root(): void
    {
        $this->assertTrue(PathPatterns::matches(['bin/*'], '/project/', '/project/bin/app'));
    }

    public function test_a_file_outside_the_root_is_matched_as_given(): void
    {
        $this->assertFalse(PathPatterns::matches(['bin/*'], '/project', '/elsewhere/bin/app'));
    }
}
