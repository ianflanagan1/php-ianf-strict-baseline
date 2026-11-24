<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

/**
 * A worker that sleeps before polling its queue again.
 */
final class SleepsBeforePolling
{
    public function wait(): void
    {
        usleep(250_000);
        time_nanosleep(0, 1000);
        time_sleep_until(1.0);
    }
}
