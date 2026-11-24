<?php

declare(strict_types=1);

namespace App\Tests;

use App\Ports\Clock;
use PHPUnit\Framework\TestCase;

final class UsesMockery extends TestCase
{
    public function test_a_mockery_mock(): void
    {
        self::assertNotNull(\Mockery::mock(Clock::class));
    }
}
