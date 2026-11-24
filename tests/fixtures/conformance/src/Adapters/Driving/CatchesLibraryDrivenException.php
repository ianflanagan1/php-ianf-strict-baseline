<?php

declare(strict_types=1);

namespace App\Adapters\Driving;

use App\Orchestrators\Greeter;
use IanFlanagan\SomeLibrary\Adapters\Driven\DocumentMissing;

/**
 * Matches a driven adapter's error type that a first-party dependency declares.
 */
final class CatchesLibraryDrivenException
{
    public function __construct(private readonly Greeter $greeter) {}

    public function handle(): string
    {
        try {
            return $this->greeter->hello();
        } catch (\Throwable $e) {
            return $e instanceof DocumentMissing ? '404' : '500';
        }
    }
}
