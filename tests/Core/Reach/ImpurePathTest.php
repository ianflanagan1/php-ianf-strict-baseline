<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Tests\Core\Reach;

use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Calls;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\CannotResolve;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\ImpurePath;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Loads;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Performs;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Step;
use PHPUnit\Framework\TestCase;

final class ImpurePathTest extends TestCase
{
    public function test_code_that_calls_only_pure_code_has_no_impure_path(): void
    {
        $graph = [
            'f' => [Calls::to('g', 'g()', '/v/g.php')],
            'g' => [],
        ];

        $this->assertNull(ImpurePath::from($graph, 'f'));
    }

    public function test_the_path_leads_from_the_start_through_each_call_to_the_operation(): void
    {
        $graph = [
            'stamp' => [Calls::to('seconds', 'Clock::seconds()', '/v/Clock.php')],
            'seconds' => [Performs::operation('time(), which reads the clock')],
        ];

        $path = ImpurePath::from($graph, 'stamp');

        $this->assertSame(['Clock::seconds()', 'time(), which reads the clock'], self::labels($path));
        $this->assertInstanceOf(Performs::class, $path[1] ?? null);
    }

    public function test_a_call_that_cannot_be_resolved_ends_a_path(): void
    {
        $graph = ['shout' => [CannotResolve::call('$fn(), a function value it built itself')]];

        $path = ImpurePath::from($graph, 'shout');

        $this->assertSame(['$fn(), a function value it built itself'], self::labels($path));
        $this->assertInstanceOf(CannotResolve::class, $path[0] ?? null);
    }

    public function test_loading_a_file_is_followed_like_a_call(): void
    {
        $graph = [
            'currency' => [Loads::file('/v/bootstrap.php', 'loads vendor/bootstrap.php')],
            '/v/bootstrap.php' => [Performs::operation('getenv(), which reads configuration')],
        ];

        $path = ImpurePath::from($graph, 'currency');

        $this->assertSame(['loads vendor/bootstrap.php', 'getenv(), which reads configuration'], self::labels($path));
        $this->assertInstanceOf(Loads::class, $path[0] ?? null);
    }

    public function test_a_path_that_loads_a_file_says_so(): void
    {
        $this->assertTrue(ImpurePath::loads([Loads::file('/v/b.php', 'loads b.php'), Performs::operation('getenv()')]));
        $this->assertFalse(ImpurePath::loads([Calls::to('b', 'b()', '/v/b.php'), Performs::operation('getenv()')]));
    }

    public function test_the_shortest_path_is_given(): void
    {
        $graph = [
            'a' => [Calls::to('b', 'b()', '/v/b.php'), Calls::to('c', 'c()', '/v/c.php')],
            'b' => [Calls::to('d', 'd()', '/v/d.php')],
            'c' => [Performs::operation('rand(), which is random')],
            'd' => [Performs::operation('time(), which reads the clock')],
        ];

        $this->assertSame(['c()', 'rand(), which is random'], self::labels(ImpurePath::from($graph, 'a')));
    }

    public function test_an_operation_the_start_performs_itself_is_found_before_any_call(): void
    {
        $graph = [
            'a' => [Calls::to('b', 'b()', '/v/b.php'), Performs::operation('rand(), which is random')],
            'b' => [Performs::operation('time(), which reads the clock')],
        ];

        $this->assertSame(['rand(), which is random'], self::labels(ImpurePath::from($graph, 'a')));
    }

    public function test_recursion_ends(): void
    {
        $graph = [
            'a' => [Calls::to('b', 'b()', '/v/b.php')],
            'b' => [Calls::to('a', 'a()', '/v/a.php')],
        ];

        $this->assertNull(ImpurePath::from($graph, 'a'));
    }

    public function test_a_target_missing_from_the_graph_reaches_nothing(): void
    {
        $this->assertNull(ImpurePath::from(['a' => [Calls::to('b', 'b()', '/v/b.php')]], 'a'));
    }

    /**
     * @param  ?list<Step>  $path
     * @return ?list<string>
     */
    private static function labels(?array $path): ?array
    {
        return $path === null ? null : ImpurePath::labels($path);
    }
}
