<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Adapters\Driving\PhpStan;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Instanceof_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;

/**
 * What the rules need to know about closed families: an enum, or a sealed type (one with a
 * phpstan-sealed tag) and its variants.
 */
final class ClosedFamily
{
    /**
     * The unit-of-code a class belongs to: the sealed type it is a variant of, since a sealed
     * type and its variants count as one unit, and otherwise the class itself.
     */
    public static function unitOf(ClassReflection $class): string
    {
        foreach ($class->getAncestors() as $ancestor) {
            if ($ancestor->getName() !== $class->getName() && $ancestor->getSealedTags() !== []) {
                return self::unitOf($ancestor);
            }
        }

        return $class->getName();
    }

    /**
     * Whether a match or switch branches on a closed family: its subject is an enum, or its arms
     * test a sealed type with instanceof.
     *
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
