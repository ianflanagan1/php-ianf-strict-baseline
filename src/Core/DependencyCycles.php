<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Core;

/**
 * Finds the cycles in a dependency graph: each set of units that depend on each other, directly
 * or through others.
 */
final class DependencyCycles
{
    /**
     * Each cycle in $graph, which maps a unit to the units it depends on. Only the graph's keys are
     * units: a dependency on anything else, such as a library's class, can't be part of a cycle.
     * Each cycle's units are sorted, and so are the cycles.
     *
     * @param  array<string, list<string>>  $graph
     * @return list<list<string>>
     */
    public static function in(array $graph): array
    {
        $reachable = [];
        foreach (array_keys($graph) as $unit) {
            $reachable[$unit] = self::reachableFrom($graph, $unit);
        }
        $cycles = [];
        foreach ($reachable as $unit => $from) {
            $cycle = array_values(array_filter(
                array_keys($from),
                static fn (string $other): bool => isset($reachable[$other][$unit]),
            ));
            if ($cycle !== []) {
                sort($cycle);
                $cycles[implode("\0", $cycle)] = $cycle;
            }
        }
        ksort($cycles);

        return array_values($cycles);
    }

    /**
     * The units reachable from $start through at least one dependency, as keys.
     *
     * @param  array<string, list<string>>  $graph
     * @return array<string, true>
     */
    private static function reachableFrom(array $graph, string $start): array
    {
        $seen = [];
        $pending = $graph[$start];
        while ($pending !== []) {
            $unit = array_pop($pending);
            if (isset($seen[$unit]) || ! isset($graph[$unit])) {
                continue;
            }
            $seen[$unit] = true;
            array_push($pending, ...$graph[$unit]);
        }

        return $seen;
    }
}
