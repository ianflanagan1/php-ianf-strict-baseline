<?php

declare(strict_types=1);

namespace App\Tests;

use App\Ports\Clock;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;

final class MocksAPort extends TestCase
{
    /**
     * @throws MockObjectException
     */
    public function test_a_mock(): void
    {
        $clock = $this->createMock(Clock::class);
        $clock->method('now')->willReturn(1);
        self::assertSame(1, $clock->now());
    }

    /**
     * @throws MockObjectException
     */
    public function test_a_stub(): void
    {
        self::assertSame(0, self::createStub(Clock::class)->now());
    }

    /**
     * @throws MockObjectException
     */
    public function test_a_stub_built_through_the_instance(): void
    {
        self::assertSame(0, $this->createStub(Clock::class)->now());
    }

    /**
     * @throws MockObjectException
     */
    public function test_a_built_mock(): void
    {
        self::assertSame(0, $this->getMockBuilder(Clock::class)->getMock()->now());
    }
}
