<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Core\Reach;

/**
 * The impure operations, as the baseline's effect bans in config/phpstan.neon list them: the
 * entries of spaze/phpstan-disallowed-calls's lists that have an allowIn list, which names the
 * roles that may perform the operation. An entry without one, such as the ban on assert(), is a
 * code rule, not an impure operation.
 */
final readonly class ImpureOperations
{
    /**
     * @param  list<OperationPatterns>  $functions
     * @param  list<OperationPatterns>  $methods  patterns of `class::method`
     * @param  list<OperationPatterns>  $classes
     * @param  list<OperationPatterns>  $constants  global constants; an entry for a class constant isn't read
     * @param  list<OperationPatterns>  $superglobals  with their $
     * @param  list<OperationPatterns>  $keywords
     */
    private function __construct(
        public array $functions,
        public array $methods,
        public array $classes,
        public array $constants,
        public array $superglobals,
        public array $keywords,
    ) {
    }

    /**
     * Reads spaze/phpstan-disallowed-calls's lists, each as its parameter of the same name holds it.
     *
     * @param  list<array<string, mixed>>  $functionCalls  disallowedFunctionCalls
     * @param  list<array<string, mixed>>  $methodCalls  disallowedMethodCalls
     * @param  list<array<string, mixed>>  $staticCalls  disallowedStaticCalls
     * @param  list<array<string, mixed>>  $namespaces  disallowedNamespaces
     * @param  list<array<string, mixed>>  $constants  disallowedConstants
     * @param  list<array<string, mixed>>  $superglobals  disallowedSuperglobals
     * @param  list<array<string, mixed>>  $keywords  disallowedKeywords
     */
    public static function fromDisallowedCalls(
        array $functionCalls,
        array $methodCalls,
        array $staticCalls,
        array $namespaces,
        array $constants,
        array $superglobals,
        array $keywords,
    ): self {
        return new self(
            self::read($functionCalls, 'function'),
            self::read([...$methodCalls, ...$staticCalls], 'method'),
            self::read($namespaces, 'namespace'),
            self::read(array_values(array_filter($constants, static fn (array $entry): bool => ! isset($entry['class']))), 'constant'),
            self::read($superglobals, 'superglobal'),
            self::read($keywords, 'keyword'),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @return list<OperationPatterns>
     */
    private static function read(array $entries, string $key): array
    {
        $read = [];
        foreach ($entries as $entry) {
            if (! is_array($entry['allowIn'] ?? null)) {
                continue;
            }
            $message = $entry['message'] ?? '';
            $read[] = OperationPatterns::of(
                self::names($entry[$key] ?? []),
                self::names($entry['exclude'] ?? []),
                is_string($message) ? trim(explode(';', $message)[0]) : '',
            );
        }

        return $read;
    }

    /**
     * @return list<string>
     */
    private static function names(mixed $names): array
    {
        $list = is_array($names) ? $names : [$names];
        $read = [];
        foreach ($list as $name) {
            if (is_string($name)) {
                $read[] = Impurity::normalise($name);
            }
        }

        return $read;
    }
}
