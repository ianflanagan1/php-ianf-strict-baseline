<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Adapters\Driving\PhpStan;

use IanFlanagan\PhpIanfStrictBaseline\Core\PathPatterns;
use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A static property or a static variable holds state shared across calls, which is global state.
 * No role's code keeps any; a project's tests may.
 *
 * @implements Rule<Stmt>
 */
final readonly class StaticStateRule implements Rule
{
    /**
     * @param  list<string>  $globalStatePaths  where global state is allowed, as fnmatch() patterns from $root
     */
    public function __construct(private array $globalStatePaths, private string $root)
    {
    }

    #[Override]
    public function getNodeType(): string
    {
        return Stmt::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $what = match (true) {
            $node instanceof Stmt\Property && $node->isStatic() => 'A static property',
            $node instanceof Stmt\Static_ => 'A static variable',
            default => null,
        };
        if ($what === null || PathPatterns::matches($this->globalStatePaths, $this->root, $scope->getFile())) {
            return [];
        }

        return [
            RuleErrorBuilder::message("{$what} is global state, shared across calls. Pass the state in, or keep it in an object the caller holds.")
                ->identifier('phpBaseline.staticState')
                ->build(),
        ];
    }
}
