<?php

declare(strict_types=1);

namespace App\Tests;

use App\Ports\Clock;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophet;

final class UsesProphecy extends TestCase
{
    public function test_a_prophecy(): void
    {
        self::assertNotNull((new Prophet)->prophesize(Clock::class));
    }
}
