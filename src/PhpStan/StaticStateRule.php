<?php

declare(strict_types=1);

namespace IanFlanagan\PhpBaseline\PhpStan;

use IanFlanagan\PhpBaseline\Core\PathPatterns;
use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A static property or a static variable holds state shared across calls, which is global state.
 * Only the roles that may perform effects keep any.
 *
 * @implements Rule<Stmt>
 */
final readonly class StaticStateRule implements Rule
{
    /**
     * @param  list<string>  $effectPaths  where effects are allowed, as fnmatch() patterns from $root
     */
    public function __construct(private array $effectPaths, private string $root)
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
        if ($what === null || PathPatterns::matches($this->effectPaths, $this->root, $scope->getFile())) {
            return [];
        }

        return [
            RuleErrorBuilder::message("{$what} is global state, shared across calls. Pass the state in, or keep it in an object the caller holds.")
                ->identifier('phpBaseline.staticState')
                ->build(),
        ];
    }
}
