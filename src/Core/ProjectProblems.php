<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Core;

/**
 * Every way a project falls short of the baseline, each phrased as what to change.
 */
final class ProjectProblems
{
    /** The .gitignore line for the directory every project's tools keep their caches in. */
    // COUPLED:tool-cache-dir: project-problems
    public const string CACHE_IGNORE = '/cache/';

    /**
     * @param  ?string  $gitignore  the project's .gitignore, or null when it has none
     * @return list<string>
     */
    public static function find(?string $gitignore): array
    {
        if ($gitignore === null) {
            return ['No .gitignore: add one with the line `'.self::CACHE_IGNORE.'`, for the directory tools keep their caches in.'];
        }
        // Git ignores a line's trailing spaces.
        foreach (preg_split('/\r?\n/', $gitignore) ?: [] as $line) {
            if (rtrim($line, ' ') === self::CACHE_IGNORE) {
                return [];
            }
        }

        return ['.gitignore: add the line `'.self::CACHE_IGNORE.'`, for the directory tools keep their caches in.'];
    }
}
