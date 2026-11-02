<?php

declare(strict_types=1);

namespace IanFlanagan\PhpBaseline\PhpStan;

use Override;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Throwable;

/**
 * A class shares behaviour by taking collaborators, not by extending another class. It may extend
 * a class the allowlist names, such as a test framework's test case, or one of PHP's own
 * exceptions, since a throwable class can't be written any other way.
 *
 * @implements Rule<InClassNode>
 */
final readonly class InheritanceRule implements Rule
{
    /**
     * @param  list<string>  $allowedParents
     */
    public function __construct(private array $allowedParents)
    {
    }

    #[Override]
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();
        $parent = $class->getParentClass();
        if ($parent === null
            || in_array($parent->getName(), $this->allowedParents, true)
            || ($parent->isBuiltin() && $parent->implementsInterface(Throwable::class))) {
            return [];
        }

        return [
            RuleErrorBuilder::message("{$class->getDisplayName()} extends {$parent->getDisplayName()}. Take what it shares as a collaborator in the constructor instead, and implement an interface for the type. A framework base class goes in phpBaselineAllowedParentClasses.")
                ->identifier('phpBaseline.inheritance')
                ->build(),
        ];
    }
}
