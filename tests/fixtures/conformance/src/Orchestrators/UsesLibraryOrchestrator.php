<?php

declare(strict_types=1);

namespace App\Orchestrators;

use IanFlanagan\SomeLibrary\Converter;

final class UsesLibraryOrchestrator
{
    public function __construct(private readonly Converter $converter) {}

    public function convert(): int
    {
        return $this->converter->convert();
    }
}
