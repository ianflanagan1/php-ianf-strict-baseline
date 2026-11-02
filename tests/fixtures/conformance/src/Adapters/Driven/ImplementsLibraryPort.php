<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

use IanFlanagan\SomeLibrary\Ports\Documents;

final class ImplementsLibraryPort implements Documents
{
    #[\Override]
    public function fetch(): string
    {
        return (string) file_get_contents('rates.json');
    }
}
