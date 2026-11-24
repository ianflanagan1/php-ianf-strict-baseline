<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Adapters\Driving\PhpStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Collects each name a class uses, as the unit-of-code it depends on, for DependencyCycleRule. A
 * class is its own unit, except that a sealed type and its variants are one, named for the sealed
 * type, so the references between them drop out. A name that isn't a class, such as a
 * function's, names no unit the rule knows, so it drops out too.
 *
 * @implements Collector<Name, array{string, string, int}>
 */
final readonly class ClassDependencyCollector implements Collector
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

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
        $unit = ClosedFamily::unitOf($class);
        $name = $scope->resolveName($node);
        $dependency = $this->reflectionProvider->hasClass($name)
            ? ClosedFamily::unitOf($this->reflectionProvider->getClass($name))
            : $name;
        if (strcasecmp($dependency, $unit) === 0) {
            return null;
        }

        return [$unit, $dependency, $node->getStartLine()];
    }
}
