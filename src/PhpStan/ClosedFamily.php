<?php

declare(strict_types=1);

namespace IanFlanagan\PhpBaseline\PhpStan;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Instanceof_;
use PHPStan\Analyser\Scope;

/**
 * Whether a match or switch branches on a closed family: an enum, or a sealed type (one with a
 * phpstan-sealed tag) that its arms test with instanceof.
 */
final class ClosedFamily
{
    /**
     * @param  list<Expr>  $conditions  every arm's conditions
     */
    public static function isBranchedOn(Expr $subject, array $conditions, Scope $scope): bool
    {
        if ($scope->getType($subject)->isEnum()->yes()) {
            return true;
        }
        foreach ($conditions as $condition) {
            if ($condition instanceof Instanceof_ && self::isSealed($condition->expr, $scope)) {
                return true;
            }
        }

        return false;
    }

    private static function isSealed(Expr $expr, Scope $scope): bool
    {
        foreach ($scope->getType($expr)->getObjectClassReflections() as $class) {
            if ($class->getSealedTags() !== []) {
                return true;
            }
        }

        return false;
    }
}
