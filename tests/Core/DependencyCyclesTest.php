<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Tests\Core;

use IanFlanagan\PhpIanfStrictBaseline\Core\DependencyCycles;
use PHPUnit\Framework\TestCase;

final class DependencyCyclesTest extends TestCase
{
    public function test_a_graph_without_cycles_has_none(): void
    {
        $this->assertSame([], DependencyCycles::in(['a' => ['b'], 'b' => ['c'], 'c' => []]));
    }

    public function test_two_units_that_depend_on_each_other_are_a_cycle(): void
    {
        $this->assertSame([['a', 'b']], DependencyCycles::in(['b' => ['a'], 'a' => ['b']]));
    }

    public function test_a_cycle_through_others_is_one_cycle_of_all_its_units(): void
    {
        $this->assertSame([['a', 'b', 'c']], DependencyCycles::in(['a' => ['b'], 'b' => ['c'], 'c' => ['a'], 'd' => ['a']]));
    }

    public function test_separate_cycles_are_reported_separately_in_order(): void
    {
        $graph = ['x' => ['y'], 'y' => ['x'], 'a' => ['b'], 'b' => ['a']];

        $this->assertSame([['a', 'b'], ['x', 'y']], DependencyCycles::in($graph));
    }

    public function test_a_dependency_outside_the_graph_is_not_part_of_a_cycle(): void
    {
        $this->assertSame([], DependencyCycles::in(['a' => ['library'], 'b' => ['library']]));
    }
}
