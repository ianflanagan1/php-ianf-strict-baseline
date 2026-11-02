<?php

declare(strict_types=1);

namespace IanFlanagan\PhpBaseline\Adapters\Driving\PhpStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects each name a class uses, as the class it depends on, for DependencyCycleRule. A name
 * that isn't a class, such as a function's, names no unit the rule knows, so it drops out.
 *
 * @implements Collector<Name, array{string, string, int}>
 */
final class ClassDependencyCollector implements Collector
{
    #[Override]
    public function getNodeType(): string
    {
        return Name::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): ?array
    {
        $class = $scope->getClassReflection();
        if ($class === null || $node->isSpecialClassName()) {
            return null;
        }
        $dependency = $scope->resolveName($node);
        if (strcasecmp($dependency, $class->getName()) === 0) {
            return null;
        }

        return [$class->getName(), $dependency, $node->getStartLine()];
    }
}
