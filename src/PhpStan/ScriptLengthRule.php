<?php

declare(strict_types=1);

namespace IanFlanagan\PhpBaseline\PhpStan;

use IanFlanagan\PhpBaseline\Core\PathPatterns;
use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A script's top-level code, such as an entrypoint in bin/, is held to the same limit as a
 * function, since it is one in all but name. Declarations of classes and functions don't count.
 *
 * @implements Rule<FileNode>
 */
final readonly class ScriptLengthRule implements Rule
{
    /**
     * @param  list<string>  $paths  where scripts are limited, as fnmatch() patterns from $root
     */
    public function __construct(private array $paths, private string $root)
    {
    }

    #[Override]
    public function getNodeType(): string
    {
        return FileNode::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $lines = self::topLevelLines($node->getNodes());
        if ($lines <= FunctionLengthRule::MAX_LINES || ! PathPatterns::matches($this->paths, $this->root, $scope->getFile())) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf('This script has %d lines of top-level code; an entrypoint keeps it to %d. Move its decisions into the core and pass their results in.', $lines, FunctionLengthRule::MAX_LINES))
                ->identifier('phpBaseline.scriptLength')
                ->line(1)
                ->build(),
        ];
    }

    /**
     * @param  array<Node>  $nodes
     */
    private static function topLevelLines(array $nodes): int
    {
        $lines = 0;
        foreach ($nodes as $node) {
            $lines += match (true) {
                $node instanceof Stmt\Namespace_ => self::topLevelLines($node->stmts),
                $node instanceof Stmt\ClassLike, $node instanceof Stmt\Function_, $node instanceof Stmt\Use_,
                $node instanceof Stmt\GroupUse, $node instanceof Stmt\Declare_, $node instanceof Stmt\InlineHTML,
                $node instanceof Stmt\Nop => 0,
                default => $node->getEndLine() - $node->getStartLine() + 1,
            };
        }

        return $lines;
    }
}
