<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Core\Reach;

/**
 * Whether a name PHP gives a function, method, class, constant, superglobal or keyword is one of
 * the impure operations, and if so, the operation and what it does, as a message names them.
 */
final class Impurity
{
    public static function ofFunction(ImpureOperations $operations, string $name): ?string
    {
        $name = ltrim($name, '\\');

        return self::label($operations->functions, [self::normalise($name) => $name.'()']);
    }

    /**
     * @param  list<string>  $classes  the method's class and each of its ancestors, as any one of them may be listed
     */
    public static function ofMethod(ImpureOperations $operations, array $classes, string $method): ?string
    {
        $names = [];
        foreach ($classes as $class) {
            $class = ltrim($class, '\\');
            $names[self::normalise($class.'::'.$method)] = "{$class}::{$method}()";
        }

        return self::label($operations->methods, $names);
    }

    /**
     * @param  list<string>  $classes  the class and each of its ancestors, as any one of them may be listed
     */
    public static function ofClass(ImpureOperations $operations, array $classes): ?string
    {
        $names = [];
        foreach ($classes as $class) {
            $class = ltrim($class, '\\');
            $names[self::normalise($class)] = $class;
        }

        return self::label($operations->classes, $names);
    }

    public static function ofConstant(ImpureOperations $operations, string $name): ?string
    {
        $name = ltrim($name, '\\');

        return self::label($operations->constants, [self::normalise($name) => $name]);
    }

    /**
     * @param  string  $name  the variable's name, without its $
     */
    public static function ofSuperglobal(ImpureOperations $operations, string $name): ?string
    {
        return self::label($operations->superglobals, [self::normalise('$'.$name) => '$'.$name]);
    }

    public static function ofKeyword(ImpureOperations $operations, string $keyword): ?string
    {
        return self::label($operations->keywords, [self::normalise($keyword) => $keyword]);
    }

    /**
     * A name or pattern as the lists compare them: lowercase, without a leading backslash or a
     * trailing (), as spaze/phpstan-disallowed-calls compares function, method and class names.
     */
    public static function normalise(string $name): string
    {
        $name = strtolower(ltrim($name, '\\'));

        return str_ends_with($name, '()') ? substr($name, 0, -2) : $name;
    }

    /**
     * @param  list<OperationPatterns>  $entries
     * @param  array<string, string>  $names  each normalised name to try, and the label it gives
     */
    private static function label(array $entries, array $names): ?string
    {
        foreach ($entries as $entry) {
            foreach ($names as $name => $label) {
                if (self::matchesAny($entry->patterns, (string) $name) && ! self::matchesAny($entry->excludes, (string) $name)) {
                    return $entry->does === '' ? $label : "{$label}, which {$entry->does}";
                }
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $patterns
     */
    private static function matchesAny(array $patterns, string $name): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $name, FNM_NOESCAPE)) {
                return true;
            }
        }

        return false;
    }
}
