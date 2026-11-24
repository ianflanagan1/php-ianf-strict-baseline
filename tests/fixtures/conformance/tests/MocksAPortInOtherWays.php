<?php

declare(strict_types=1);

namespace App\Tests;

use App\Ports\Clock;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;

final class MocksAPortInOtherWays extends TestCase
{
    /**
     * @throws MockObjectException
     */
    public function test_a_configured_mock(): void
    {
        self::assertSame(1, $this->createConfiguredMock(Clock::class, ['now' => 1])->now());
    }

    /**
     * @throws MockObjectException
     */
    public function test_a_partial_mock(): void
    {
        self::assertSame(0, $this->createPartialMock(Clock::class, ['now'])->now());
    }

    /**
     * @throws MockObjectException
     */
    public function test_a_configured_stub(): void
    {
        self::assertSame(1, self::createConfiguredStub(Clock::class, ['now' => 1])->now());
    }
}
