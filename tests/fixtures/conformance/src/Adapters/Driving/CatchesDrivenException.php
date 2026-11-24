<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

use App\Adapters\Driven\RowNotFound;
use App\Orchestrators\Greeter;

/**
 * Catches a driven adapter's not-found exception to answer 404.
 */
final class CatchesDrivenException
{
    public function __construct(private readonly Greeter $greeter) {}

    public function handle(): string
    {
        try {
            return $this->greeter->hello();
        } catch (RowNotFound) {
            return '404';
        }
    }
}
