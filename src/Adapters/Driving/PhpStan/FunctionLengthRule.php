<?php

declare(strict_types=1);

namespace IanFlanagan\PhpBaseline\Adapters\Driving\PhpStan;

use IanFlanagan\PhpBaseline\Core\PathPatterns;
use Override;
use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Each function in an adapter or an entrypoint stays short enough that no decision can
 * hide in it.
 *
 * @implements Rule<FunctionLike>
 */
final readonly class FunctionLengthRule implements Rule
{
    public const int MAX_LINES = 50;

    /**
     * @param  list<string>  $paths  where functions are limited, as fnmatch() patterns from $root
     */
    public function __construct(private array $paths, private string $root)
    {
    }

    #[Override]
    public function getNodeType(): string
    {
        return FunctionLike::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $lines = $node->getEndLine() - $node->getStartLine() + 1;
        if ($lines <= self::MAX_LINES || ! PathPatterns::matches($this->paths, $this->root, $scope->getFile())) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf('This function is %d lines; adapters and entrypoints keep each function to %d. Move its decisions into the core and pass their results in.', $lines, self::MAX_LINES))
                ->identifier('phpBaseline.functionLength')
                ->build(),
        ];
    }
}
