<?php

declare(strict_types=1);

namespace App\Core;

use IanFlanagan\SomeLibrary\Converter;

final class UsesLibraryOrchestrator
{
    public function convert(Converter $converter): int
    {
        return $converter->convert();
    }
}
