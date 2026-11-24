<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Adapters\Driving\PhpStan;

use IanFlanagan\PhpIanfStrictBaseline\Core\PathPatterns;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Calls;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\CannotResolve;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\ImpureOperations;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Impurity;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Loads;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Performs;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Step;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\TypeCombinator;

/**
 * What a node of PHP code does that bears on whether the core may reach it: the calls it makes
 * to code outside the core, which are followed, the files it loads, the impure operations it
 * performs and the calls it can't resolve. A call is resolved by name, by $this, and by the type
 * PHPStan gives the value it is made on, which comes from declared types of properties,
 * parameters and results. Code in the core is analysed where it is, so a call to it is no step.
 */
final readonly class CallSteps
{
    /**
     * @param  list<string>  $corePaths  the core's files, as fnmatch() patterns from $root
     */
    public function __construct(
        private ReflectionProvider $reflection,
        private ImpureOperations $operations,
        private ExprPrinter $printer,
        private array $corePaths,
        private string $root,
    ) {
    }

    /**
     * The calls and loads a node makes, as the core's own code is checked for them.
     *
     * @return list<Step>
     */
    public function calls(Node $node, Scope $scope): array
    {
        $steps = match (true) {
            $node instanceof Expr\FuncCall => $this->functionCall($node, $scope),
            $node instanceof Expr\MethodCall, $node instanceof Expr\NullsafeMethodCall => $this->methodCall($node->var, $node->name, $scope),
            $node instanceof Expr\StaticCall => $this->staticCall($node, $scope),
            $node instanceof Expr\New_ => $this->construction($node, $scope),
            $node instanceof Expr\ClassConstFetch, $node instanceof Expr\StaticPropertyFetch => $this->classFetch($node->class, $scope),
            $node instanceof Expr\Include_ => $this->inclusion($node, $scope),
            default => [],
        };

        return $node instanceof Expr\CallLike ? [...$steps, ...$this->callablesPassed($node, $scope)] : $steps;
    }

    /**
     * A first-class callable passed to a call, such as foo(...), counts as a call to what it
     * names, since the callee may call it. The walk shows the callable only as PHPStan's own node
     * for it, and only here, as an argument, as PHP's.
     *
     * @return list<Step>
     */
    private function callablesPassed(Expr\CallLike $node, Scope $scope): array
    {
        if ($node->isFirstClassCallable()) {
            return [];
        }
        $steps = [];
        foreach ($node->getArgs() as $arg) {
            if ($arg->value instanceof Expr\CallLike && $arg->value->isFirstClassCallable()) {
                $steps = [...$steps, ...$this->calls($arg->value, $scope)];
            }
        }

        return $steps;
    }

    /**
     * Everything a node of code outside the core does: its calls and loads, and the impure
     * operations it performs, which the bans report only in the project's own code.
     *
     * @return list<Step>
     */
    public function of(Node $node, Scope $scope): array
    {
        return [...$this->calls($node, $scope), ...$this->operation($node, $scope)];
    }

    /**
     * @return list<Step>
     */
    private function operation(Node $node, Scope $scope): array
    {
        $operation = match (true) {
            $node instanceof Stmt\Echo_ => Impurity::ofFunction($this->operations, 'echo'),
            $node instanceof Expr\Print_ => Impurity::ofFunction($this->operations, 'print'),
            $node instanceof Expr\Exit_ => Impurity::ofFunction($this->operations, $node->getAttribute('kind') === Expr\Exit_::KIND_DIE ? 'die' : 'exit'),
            $node instanceof Stmt\Global_ => Impurity::ofKeyword($this->operations, 'global'),
            $node instanceof Stmt\Static_ => 'a static variable, which keeps state between calls',
            $node instanceof Expr\Assign, $node instanceof Expr\AssignOp, $node instanceof Expr\AssignRef,
            $node instanceof Expr\PreInc, $node instanceof Expr\PreDec, $node instanceof Expr\PostInc, $node instanceof Expr\PostDec => $this->staticWrite($node->var),
            $node instanceof Expr\Variable => is_string($node->name) ? Impurity::ofSuperglobal($this->operations, $node->name) : null,
            $node instanceof Expr\ConstFetch => Impurity::ofConstant($this->operations, $this->reflection->resolveConstantName($node->name, $scope) ?? $node->name->toString()),
            default => null,
        };
        $unresolved = match (true) {
            $node instanceof Expr\Eval_ => 'eval(), which runs code built as text',
            $node instanceof Expr\Variable && ! is_string($node->name) => $this->printer->printExpr($node).', a variable named by a value',
            default => null,
        };

        return [
            ...($operation === null ? [] : [Performs::operation($operation)]),
            ...($unresolved === null ? [] : [CannotResolve::call($unresolved)]),
        ];
    }

    /**
     * The static property a write changes, through any array element or property of it.
     */
    private function staticWrite(Expr $target): ?string
    {
        while ($target instanceof Expr\ArrayDimFetch || $target instanceof Expr\PropertyFetch) {
            $target = $target->var;
        }
        if (! $target instanceof Expr\StaticPropertyFetch) {
            return null;
        }

        return 'writes '.$this->printer->printExpr($target).', a static property, which keeps state between calls';
    }

    /**
     * @return list<Step>
     */
    private function functionCall(Expr\FuncCall $node, Scope $scope): array
    {
        if (! $node->name instanceof Name) {
            return $this->callable($node->name, $scope);
        }
        if (! $this->reflection->hasFunction($node->name, $scope)) {
            return [CannotResolve::call($node->name->toString()."(), which the check can't find")];
        }
        $function = $this->reflection->getFunction($node->name, $scope);
        if ($function->isBuiltin()) {
            return $this->builtinCall($function, $node, $scope);
        }
        $file = $function->getFileName();
        if ($file === null) {
            return [CannotResolve::call($function->getName()."(), whose code the check can't find")];
        }

        return $this->inCore($file) ? [] : [Calls::to(self::functionKey($function->getName()), $function->getName().'()', $file)];
    }

    /**
     * A function PHP declares: impure if the bans list it, and a call through each function value
     * it is passed, which it calls.
     *
     * @return list<Step>
     */
    private function builtinCall(FunctionReflection $function, Expr\FuncCall $node, Scope $scope): array
    {
        $operation = Impurity::ofFunction($this->operations, $function->getName());
        $steps = $operation === null ? [] : [Performs::operation($operation)];
        if ($node->isFirstClassCallable()) {
            return $steps;
        }
        $parameters = $function->getVariants()[0]->getParameters();
        foreach ($node->getArgs() as $position => $arg) {
            $parameter = $parameters[$position] ?? null;
            $callsIt = in_array(strtolower($function->getName()), ['call_user_func', 'call_user_func_array'], true) && $position === 0;
            if ($callsIt || ($parameter !== null && TypeCombinator::removeNull($parameter->getType())->isCallable()->yes())) {
                $steps = [...$steps, ...$this->callable($arg->value, $scope)];
            }
        }

        return $steps;
    }

    /**
     * A call through a function value: the caller's when the code received it as an argument,
     * followed when it names a function, and otherwise a call the check can't resolve. A closure
     * written in place is code of the function it is written in, and is read with it.
     *
     * @return list<Step>
     */
    private function callable(Expr $value, Scope $scope): array
    {
        if ($value instanceof Expr\Closure || $value instanceof Expr\ArrowFunction || $this->isReceived($value, $scope)) {
            return [];
        }
        if ($value instanceof Expr\CallLike && $value->isFirstClassCallable()) {
            // Followed as a call by callablesPassed().
            return [];
        }
        $type = $scope->getType($value);
        if ($type->isNull()->yes()) {
            return [];
        }
        $names = $type->getConstantStrings();
        if (count($names) === 1 && ! str_contains($names[0]->getValue(), '::')) {
            return $this->functionCall(new Expr\FuncCall(new FullyQualified($names[0]->getValue())), $scope);
        }

        return [CannotResolve::call($this->printer->printExpr($value)."(), a call through a function value the code didn't receive as an argument")];
    }

    /**
     * @return list<Step>
     */
    private function methodCall(Expr $object, Identifier|Expr $name, Scope $scope): array
    {
        $received = $this->isReceived($object, $scope);
        $call = $this->printer->printExpr($object).'->'.($name instanceof Identifier ? $name->toString() : '{'.$this->printer->printExpr($name).'}').'()';
        if (! $name instanceof Identifier) {
            return $received ? [] : [CannotResolve::call("{$call}, a method named by a value")];
        }
        $classes = $scope->getType($object)->getObjectClassReflections();
        if ($classes === []) {
            return $received ? [] : [CannotResolve::call("{$call}, on a value whose class the check can't tell")];
        }
        $steps = [];
        foreach ($classes as $class) {
            $steps = [...$steps, ...$this->classSteps($class), ...$this->method($class, $name->toString(), $received, $scope)];
        }

        return $steps;
    }

    /**
     * @return list<Step>
     */
    private function staticCall(Expr\StaticCall $node, Scope $scope): array
    {
        $class = $this->namedClass($node->class, $scope);
        if (! $class instanceof ClassReflection) {
            return $class;
        }
        if (! $node->name instanceof Identifier) {
            return [CannotResolve::call($class->getName().'::{'.$this->printer->printExpr($node->name).'}(), a method named by a value')];
        }

        return [...$this->classSteps($class), ...$this->method($class, $node->name->toString(), false, $scope)];
    }

    /**
     * @return list<Step>
     */
    private function construction(Expr\New_ $node, Scope $scope): array
    {
        if ($node->class instanceof Stmt\Class_) {
            return $this->inCore($scope->getFile()) ? [] : [CannotResolve::call("new class, an anonymous class whose methods the check doesn't follow")];
        }
        $class = $this->namedClass($node->class, $scope);
        if (! $class instanceof ClassReflection) {
            return $class;
        }
        $steps = $this->classSteps($class);
        foreach (['__construct', '__destruct'] as $method) {
            if ($class->hasMethod($method) || ($class->isBuiltin() && $method === '__construct')) {
                $steps = [...$steps, ...$this->method($class, $method, false, $scope)];
            }
        }

        return $steps;
    }

    /**
     * @return list<Step>
     */
    private function classFetch(Name|Expr $class, Scope $scope): array
    {
        $reflection = $this->namedClass($class, $scope);

        return $reflection instanceof ClassReflection ? $this->classSteps($reflection) : $reflection;
    }

    /**
     * The class a name in the code gives, or the steps to report when the check can't tell it: a
     * class named by a value is the caller's when the code received the value as an argument.
     *
     * @return ClassReflection|list<Step>
     */
    private function namedClass(Name|Expr $class, Scope $scope): ClassReflection|array
    {
        if (! $class instanceof Name) {
            return $this->isReceived($class, $scope) ? [] : [CannotResolve::call($this->printer->printExpr($class).', a class named by a value')];
        }
        $name = $scope->resolveName($class);
        if (! $this->reflection->hasClass($name)) {
            return [CannotResolve::call("{$name}, a class the check can't find")];
        }

        return $this->reflection->getClass($name);
    }

    /**
     * Using a class: impure if the bans list it or an ancestor, and loading the file of each of
     * them outside the core.
     *
     * @return list<Step>
     */
    private function classSteps(ClassReflection $class): array
    {
        $operation = Impurity::ofClass($this->operations, self::ancestorNames($class));
        $steps = $operation === null ? [] : [Performs::operation($operation)];
        foreach ([$class, ...$class->getParents(), ...$class->getInterfaces(), ...$class->getTraits(true)] as $loaded) {
            $file = $loaded->getFileName();
            if (! $loaded->isBuiltin() && $file !== null && ! $this->inCore($file)) {
                $steps[] = $this->loads($file);
            }
        }

        return $steps;
    }

    /**
     * A call to a method of a class: impure if PHP declares it and the bans list it, followed when
     * the code outside the core declares it, and the caller's when it is an interface's or an
     * abstract method called on a value the code received as an argument. Called on anything else,
     * any implementation could answer it, so the check can't resolve it.
     *
     * @return list<Step>
     */
    private function method(ClassReflection $class, string $name, bool $received, Scope $scope): array
    {
        if (! $class->hasMethod($name)) {
            if (! $class->isBuiltin()) {
                return [CannotResolve::call($class->getName()."::{$name}(), which its class doesn't declare")];
            }
            // Such as the constructor of stdClass, which PHP declares none for.
            $operation = Impurity::ofMethod($this->operations, self::ancestorNames($class), $name);

            return $operation === null ? [] : [Performs::operation($operation)];
        }
        $method = $class->getMethod($name, $scope);
        $declaring = $method->getDeclaringClass();
        if ($declaring->isBuiltin()) {
            $operation = Impurity::ofMethod($this->operations, self::ancestorNames($class), $method->getName());

            return $operation === null ? [] : [Performs::operation($operation)];
        }
        $file = $declaring->getFileName();
        $label = $declaring->getName().'::'.$method->getName().'()';
        if ($file !== null && $this->inCore($file)) {
            return [];
        }
        $abstract = $method->isAbstract();
        if ($declaring->isInterface() || $abstract === true || ($abstract instanceof TrinaryLogic && $abstract->yes())) {
            return $received ? [] : [CannotResolve::call("{$label}, which any implementation of it could answer")];
        }

        return $file === null
            ? [CannotResolve::call("{$label}, whose code the check can't find")]
            : [Calls::to(self::methodKey($declaring->getName(), $method->getName()), $label, $file)];
    }

    /**
     * An include or a require: of one file, whose load-time code is followed, or of a path built
     * at run time, which the check can't resolve.
     *
     * @return list<Step>
     */
    private function inclusion(Expr\Include_ $node, Scope $scope): array
    {
        $path = self::literalPath($node->expr, $scope->getFile());
        $paths = $scope->getType($node->expr)->getConstantStrings();
        if ($path === null && count($paths) === 1) {
            $path = $paths[0]->getValue();
        }
        if ($path === null) {
            return [CannotResolve::call('include '.$this->printer->printExpr($node->expr).', a file named at run time')];
        }
        $file = str_starts_with($path, '/') ? $path : dirname($scope->getFile()).'/'.$path;

        return $this->inCore($file) ? [] : [$this->loads($file)];
    }

    /**
     * The path an include names in its source: a string, __DIR__, __FILE__, or one of them joined
     * to another with a dot, as `__DIR__ . '/tokens.php'`.
     */
    private static function literalPath(Expr $path, string $file): ?string
    {
        if ($path instanceof Expr\BinaryOp\Concat) {
            $left = self::literalPath($path->left, $file);
            $right = self::literalPath($path->right, $file);

            return $left === null || $right === null ? null : $left.$right;
        }

        return match (true) {
            $path instanceof Node\Scalar\String_ => $path->value,
            $path instanceof Node\Scalar\MagicConst\Dir => dirname($file),
            $path instanceof Node\Scalar\MagicConst\File => $file,
            default => null,
        };
    }

    /**
     * Whether a value is one of the parameters of the function it is used in, so that what a call
     * on it reaches is what the function's caller passes. A parameter the function assigns to
     * before the call is still taken for the caller's value.
     */
    private function isReceived(Expr $value, Scope $scope): bool
    {
        if (! $value instanceof Expr\Variable || ! is_string($value->name)) {
            return false;
        }
        $acceptors = [...($scope->getFunction()?->getVariants() ?? []), ...($scope->getAnonymousFunctionReflection() === null ? [] : [$scope->getAnonymousFunctionReflection()])];
        foreach ($acceptors as $acceptor) {
            foreach ($acceptor->getParameters() as $parameter) {
                if ($parameter->getName() === $value->name) {
                    return true;
                }
            }
        }

        return false;
    }

    private function loads(string $file): Loads
    {
        $resolved = realpath($file);

        return Loads::file($resolved === false ? $file : $resolved, 'loads '.$this->relative($resolved === false ? $file : $resolved));
    }

    private function inCore(string $file): bool
    {
        return PathPatterns::matches($this->corePaths, $this->root, $file);
    }

    private function relative(string $file): string
    {
        $prefix = rtrim($this->root, '/').'/';

        return str_starts_with($file, $prefix) ? substr($file, strlen($prefix)) : $file;
    }

    /**
     * @return list<string>
     */
    private static function ancestorNames(ClassReflection $class): array
    {
        return array_values(array_map(static fn (ClassReflection $ancestor): string => $ancestor->getName(), $class->getAncestors()));
    }

    public static function functionKey(string $name): string
    {
        return 'function:'.strtolower(ltrim($name, '\\'));
    }

    public static function methodKey(string $class, string $method): string
    {
        return 'method:'.strtolower(ltrim($class, '\\')).'::'.strtolower($method);
    }
}
