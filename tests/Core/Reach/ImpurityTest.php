<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Tests\Core\Reach;

use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\ImpureOperations;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Impurity;
use PHPUnit\Framework\TestCase;

final class ImpurityTest extends TestCase
{
    public function test_a_function_an_effect_entry_lists_is_impure(): void
    {
        $operations = self::operations(['functionCalls' => [
            ['function' => ['time()', 'microtime()'], 'message' => 'reads the clock; take the time as a value', 'allowIn' => ['src/Adapters/*']],
        ]]);

        $this->assertSame('time(), which reads the clock', Impurity::ofFunction($operations, 'time'));
    }

    public function test_function_names_match_without_their_case_or_a_leading_backslash(): void
    {
        $operations = self::operations(['functionCalls' => [
            ['function' => 'GetEnv()', 'message' => 'reads configuration', 'allowIn' => ['bin/*']],
        ]]);

        $this->assertSame('getenv(), which reads configuration', Impurity::ofFunction($operations, '\\getenv'));
    }

    public function test_a_function_matches_a_wildcard_unless_excluded(): void
    {
        $operations = self::operations(['functionCalls' => [
            ['function' => 'mysqli_*()', 'exclude' => ['mysqli_fetch_object()'], 'message' => 'reaches a database', 'allowIn' => []],
        ]]);

        $this->assertSame('mysqli_query(), which reaches a database', Impurity::ofFunction($operations, 'mysqli_query'));
        $this->assertNull(Impurity::ofFunction($operations, 'mysqli_fetch_object'));
    }

    public function test_an_entry_without_an_allow_in_list_is_a_code_rule_and_not_an_impure_operation(): void
    {
        $operations = self::operations(['functionCalls' => [
            ['function' => 'assert()', 'message' => 'narrows a type with a test production builds skip'],
        ]]);

        $this->assertNull(Impurity::ofFunction($operations, 'assert'));
    }

    public function test_a_function_no_entry_lists_is_not_impure(): void
    {
        $operations = self::operations(['functionCalls' => [
            ['function' => 'time()', 'message' => 'reads the clock', 'allowIn' => []],
        ]]);

        $this->assertNull(Impurity::ofFunction($operations, 'strtoupper'));
    }

    public function test_a_method_of_a_listed_class_or_its_ancestor_is_impure(): void
    {
        $operations = self::operations(['methodCalls' => [
            ['method' => ['DateTimeImmutable::__construct()'], 'message' => 'may read the clock; parse with createFromFormat()', 'allowIn' => []],
        ]]);

        $this->assertSame(
            'DateTimeImmutable::__construct(), which may read the clock',
            Impurity::ofMethod($operations, ['Acme\\Now', 'DateTimeImmutable'], '__construct'),
        );
        $this->assertNull(Impurity::ofMethod($operations, ['DateTimeImmutable'], 'format'));
    }

    public function test_static_call_entries_are_methods_too(): void
    {
        $operations = self::operations(['staticCalls' => [
            ['method' => 'acme\\env::READ()', 'message' => 'reads configuration', 'allowIn' => []],
        ]]);

        $this->assertSame('Acme\\Env::read(), which reads configuration', Impurity::ofMethod($operations, ['Acme\\Env'], 'read'));
    }

    public function test_a_class_in_a_listed_namespace_is_impure(): void
    {
        $operations = self::operations(['namespaces' => [
            ['namespace' => ['PDO', 'PDO\\*'], 'message' => 'reaches a database', 'allowIn' => []],
            ['namespace' => 'Random\\*', 'message' => 'is random; take the value from a port', 'allowIn' => []],
        ]]);

        $this->assertSame('PDO, which reaches a database', Impurity::ofClass($operations, ['Acme\\Db', 'PDO']));
        $this->assertSame('Random\\Randomizer, which is random', Impurity::ofClass($operations, ['Random\\Randomizer']));
        $this->assertNull(Impurity::ofClass($operations, ['PDOStatementLike']));
    }

    public function test_a_global_constant_is_impure_and_a_class_constant_entry_is_not_read(): void
    {
        $operations = self::operations(['constants' => [
            ['constant' => ['STDIN', 'STDOUT'], 'message' => 'uses the console', 'allowIn' => []],
            ['class' => 'PDO', 'constant' => 'FETCH_CLASS', 'message' => 'builds an object', 'allowIn' => []],
        ]]);

        $this->assertSame('STDOUT, which uses the console', Impurity::ofConstant($operations, '\\STDOUT'));
        $this->assertNull(Impurity::ofConstant($operations, 'FETCH_CLASS'));
    }

    public function test_a_superglobal_and_a_keyword_are_impure(): void
    {
        $operations = self::operations([
            'superglobals' => [['superglobal' => ['$_ENV', '$_SERVER'], 'message' => 'reads configuration', 'allowIn' => []]],
            'keywords' => [['keyword' => 'global', 'message' => 'is global state, which no code keeps; pass state in', 'allowIn' => []]],
        ]);

        $this->assertSame('$_SERVER, which reads configuration', Impurity::ofSuperglobal($operations, '_SERVER'));
        $this->assertNull(Impurity::ofSuperglobal($operations, 'name'));
        $this->assertSame('global, which is global state, which no code keeps', Impurity::ofKeyword($operations, 'global'));
    }

    public function test_an_entry_without_a_message_names_only_the_operation(): void
    {
        $operations = self::operations(['functionCalls' => [['function' => 'sleep()', 'allowIn' => []]]]);

        $this->assertSame('sleep()', Impurity::ofFunction($operations, 'sleep'));
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $lists  each of spaze/phpstan-disallowed-calls's lists the test sets, by its parameter's name
     */
    private static function operations(array $lists): ImpureOperations
    {
        return ImpureOperations::fromDisallowedCalls(
            $lists['functionCalls'] ?? [],
            $lists['methodCalls'] ?? [],
            $lists['staticCalls'] ?? [],
            $lists['namespaces'] ?? [],
            $lists['constants'] ?? [],
            $lists['superglobals'] ?? [],
            $lists['keywords'] ?? [],
        );
    }
}
