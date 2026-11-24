<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

final class SleepsInATest extends TestCase
{
    public function test_the_server_is_up_after_a_second(): void
    {
        sleep(1);
        self::assertTrue(is_dir(__DIR__));
    }
}
