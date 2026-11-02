<?php

declare(strict_types=1);

namespace IanFlanagan\PhpBaseline\Adapters\Driving\PhpStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * No parameter has a default value, so every caller passes every argument, and adding a parameter
 * makes PHPStan find each call. A method that implements or overrides another may keep the
 * defaults the signature it matches has.
 *
 * @implements Rule<FunctionLike>
 */
final class ParameterDefaultRule implements Rule
{
    #[Override]
    public function getNodeType(): string
    {
        return FunctionLike::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof ClassMethod && self::matchesAnotherSignature($node, $scope)) {
            return [];
        }
        $errors = [];
        foreach ($node->getParams() as $param) {
            if ($param->default !== null) {
                $errors[] = self::error($param->var, $param->getStartLine());
            }
        }

        return $errors;
    }

    private static function matchesAnotherSignature(ClassMethod $method, Scope $scope): bool
    {
        $class = $scope->getClassReflection();
        if ($class === null) {
            return false;
        }
        foreach ([...$class->getParents(), ...$class->getInterfaces()] as $ancestor) {
            if ($ancestor->hasNativeMethod($method->name->toString())) {
                return true;
            }
        }

        return false;
    }

    private static function error(Node\Expr $var, int $line): IdentifierRuleError
    {
        $name = $var instanceof Variable && is_string($var->name) ? '$'.$var->name : 'This parameter';

        return RuleErrorBuilder::message("{$name} has a default value. Remove it, so every caller passes the argument.")
            ->identifier('phpBaseline.parameterDefault')
            ->line($line)
            ->build();
    }
}
