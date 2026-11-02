<?php

declare(strict_types=1);

namespace IanFlanagan\PhpBaseline\Adapters\Driving\PhpStan;

use IanFlanagan\PhpBaseline\Core\DependencyCycles;
use LogicException;
use Override;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * No class depends on itself through others. Each cycle is reported once, where its first class,
 * in name order, uses the next.
 *
 * @implements Rule<CollectedDataNode>
 */
final class DependencyCycleRule implements Rule
{
    #[Override]
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $graph = [];
        $names = [];
        $uses = [];
        foreach ($node->get(ClassDependencyCollector::class) as $file => $dependencies) {
            foreach ($dependencies as [$class, $dependency, $line]) {
                [$from, $to] = [strtolower($class), strtolower($dependency)];
                $names[$from] = $class;
                $graph[$from][] = $to;
                $uses[$from][$to] ??= [$file, $line];
            }
        }
        $errors = [];
        foreach (DependencyCycles::in($graph) as $cycle) {
            $first = $cycle[0];
            [$file, $line] = self::firstUseWithin($uses[$first], $cycle);
            $members = implode(', ', array_map(static fn (string $unit): string => $names[$unit], $cycle));
            $errors[] = RuleErrorBuilder::message("These classes depend on each other in a cycle: {$members}. Break it: move what they share into a class that depends on none of them, or have one take another through an interface it declares.")
                ->identifier('phpBaseline.dependencyCycle')
                ->file($file)
                ->line($line)
                ->build();
        }

        return $errors;
    }

    /**
     * Where a class first uses another class of its cycle.
     *
     * @param  array<string, array{string, int}>  $uses  the class's uses, by the class used
     * @param  list<string>  $cycle
     * @return array{string, int}
     */
    private static function firstUseWithin(array $uses, array $cycle): array
    {
        foreach ($uses as $used => $where) {
            if (in_array($used, $cycle, true)) {
                return $where;
            }
        }
        throw new LogicException('a class in a cycle uses no other class of it');
    }
}
