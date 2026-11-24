<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Core\Reach;

/**
 * The way from a piece of code to an impure operation, or to a call that can't be resolved,
 * through the calls and loads that lead there.
 */
final class ImpurePath
{
    /**
     * The shortest path from $start to a step that performs an impure operation or can't be
     * resolved, or null when every step it reaches is pure. A target missing from the graph does
     * nothing.
     *
     * @param  array<string, list<Step>>  $graph  what each piece of code does, by its key
     * @return ?list<Step>  the steps after $start, the last of them a Performs or a CannotResolve
     */
    public static function from(array $graph, string $start): ?array
    {
        $seen = [$start => true];
        $paths = [$start => []];
        while ($paths !== []) {
            $next = [];
            foreach ($paths as $key => $path) {
                foreach ($graph[$key] ?? [] as $step) {
                    $target = self::target($step);
                    if ($target === null) {
                        return [...$path, $step];
                    }
                    if (! isset($seen[$target])) {
                        $seen[$target] = true;
                        $next[$target] = [...$path, $step];
                    }
                }
            }
            $paths = $next;
        }

        return null;
    }

    /**
     * @param  list<Step>  $path
     * @return list<string>
     */
    public static function labels(array $path): array
    {
        return array_map(
            static fn (Step $step): string => match (true) {
                $step instanceof Calls, $step instanceof Performs, $step instanceof CannotResolve, $step instanceof Loads => $step->label,
            },
            $path,
        );
    }

    /**
     * Whether the path runs a file's load-time code on its way.
     *
     * @param  list<Step>  $path
     */
    public static function loads(array $path): bool
    {
        foreach ($path as $step) {
            if ($step instanceof Loads) {
                return true;
            }
        }

        return false;
    }

    /**
     * The key of the code a step leads to, or null for a step that ends a path.
     */
    private static function target(Step $step): ?string
    {
        return match (true) {
            $step instanceof Calls => $step->target,
            $step instanceof Loads => $step->file,
            $step instanceof Performs, $step instanceof CannotResolve => null,
        };
    }
}
