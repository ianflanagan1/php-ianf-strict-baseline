<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Adapters\Driving\PhpStan;

use IanFlanagan\PhpIanfStrictBaseline\Core\PathPatterns;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Calls;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\CannotResolve;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\ImpurePath;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Loads;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Performs;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Step;
use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Code the core calls is pure by what its own code does, never by which package holds it or
 * where it lives: from each call, new and class reference in the core to code outside it, the
 * rule follows every call that code can make, at any depth, and the load-time code of every file
 * it loads, Composer's autoload files among them. It reports a call that reaches one of the
 * impure operations the baseline's effect bans list, or state kept between calls, and a call it
 * can't resolve, since it can't show that one is pure.
 *
 * @implements Rule<Expr>
 */
final readonly class CoreCallsReachNothingImpureRule implements Rule
{
    /** The key of the core's own call in the graph, which no function, method or file has. */
    private const string START = "\0core";

    /**
     * @param  list<string>  $corePaths  the core's files, as fnmatch() patterns from $root
     */
    public function __construct(
        private CallSteps $steps,
        private CodeReach $reach,
        private array $corePaths,
        private string $root,
    ) {
    }

    #[Override]
    public function getNodeType(): string
    {
        return Expr::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if (! PathPatterns::matches($this->corePaths, $this->root, $scope->getFile())) {
            return [];
        }
        // An impure operation the core performs itself is the effect bans' to report.
        $steps = array_values(array_filter($this->steps->calls($node, $scope), static fn (Step $step): bool => ! $step instanceof Performs));
        $graph = [];
        foreach ($steps as $step) {
            if ($step instanceof Calls || $step instanceof Loads) {
                $graph = $this->reach->from($step);
            }
        }
        $path = ImpurePath::from([self::START => $steps] + $graph, self::START);

        return $path === null ? [] : [self::error($path)];
    }

    /**
     * @param  list<Step>  $path  from the core's call to what makes it impure
     */
    private static function error(array $path): IdentifierRuleError
    {
        $route = implode(' -> ', ImpurePath::labels($path));
        if ($path[count($path) - 1] instanceof CannotResolve) {
            return RuleErrorBuilder::message(
                "The core calls code the check can't show is pure: {$route}. Pass the function or object in as an argument, call it on a type the code declares, or move the call to the shell.",
            )->identifier('phpBaseline.coreCallUnresolved')->build();
        }
        if (ImpurePath::loads($path)) {
            return RuleErrorBuilder::message(
                "Loading the code the core uses performs an impure operation: {$route}. Move the use of that code to the shell, or depend on a package whose load-time code does nothing.",
            )->identifier('phpBaseline.coreLoadingDoesImpure')->build();
        }

        return RuleErrorBuilder::message(
            "The core reaches an impure operation: {$route}. Take the value it gives as an argument instead, or move the call to the shell.",
        )->identifier('phpBaseline.coreCallReachesImpure')->build();
    }
}
