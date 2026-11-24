<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Core;

/**
 * Whether a file is in one of a config's paths, which are fnmatch() patterns from the project's
 * root, as spaze/phpstan-disallowed-calls takes them in allowIn.
 */
final class PathPatterns
{
    /**
     * @param  list<string>  $patterns
     */
    public static function matches(array $patterns, string $root, string $file): bool
    {
        $prefix = rtrim($root, '/').'/';
        $relative = str_starts_with($file, $prefix) ? substr($file, strlen($prefix)) : $file;
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $relative)) {
                return true;
            }
        }

        return false;
    }
}
