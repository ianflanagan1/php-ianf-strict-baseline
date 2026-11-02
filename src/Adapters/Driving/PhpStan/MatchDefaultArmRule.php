<?php

declare(strict_types=1);

namespace IanFlanagan\PhpBaseline\Adapters\Driving\PhpStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\MatchArm;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A match on an enum or a sealed family has no default arm, so PHPStan reports it when a case is
 * added. PHP needs no catch-all: a match that handles no arm throws UnhandledMatchError.
 *
 * @implements Rule<Match_>
 */
final class MatchDefaultArmRule implements Rule
{
    #[Override]
    public function getNodeType(): string
    {
        return Match_::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $default = null;
        $conditions = [];
        foreach ($node->arms as $arm) {
            $default ??= $arm->conds === null ? $arm : null;
            array_push($conditions, ...$arm->conds ?? []);
        }
        if (! $default instanceof MatchArm || ! ClosedFamily::isBranchedOn($node->cond, $conditions, $scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('This match on a closed family has a default arm. Name each case instead, listing those that share a branch in one arm, so PHPStan reports the match when a case is added.')
                ->identifier('phpBaseline.matchDefaultArm')
                ->line($default->getStartLine())
                ->build(),
        ];
    }
}
