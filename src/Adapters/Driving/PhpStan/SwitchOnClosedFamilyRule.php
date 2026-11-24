<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Adapters\Driving\PhpStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Switch_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A closed family is branched on with match, which PHPStan checks handles every case, not with
 * switch, which it doesn't.
 *
 * @implements Rule<Switch_>
 */
final class SwitchOnClosedFamilyRule implements Rule
{
    #[Override]
    public function getNodeType(): string
    {
        return Switch_::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $conditions = array_values(array_filter(
            array_map(static fn (Node\Stmt\Case_ $case): ?Expr => $case->cond, $node->cases),
            static fn (?Expr $condition): bool => $condition !== null,
        ));
        if (! ClosedFamily::isBranchedOn($node->cond, $conditions, $scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('This switch branches on a closed family. Use match, with an arm for each case and no default, so PHPStan reports a missing case.')
                ->identifier('phpBaseline.switchOnClosedFamily')
                ->build(),
        ];
    }
}
