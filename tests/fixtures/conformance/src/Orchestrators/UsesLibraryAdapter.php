<?php

declare(strict_types=1);

namespace App\Orchestrators;

use IanFlanagan\SomeLibrary\Adapters\Driven\FileDocuments;

final class UsesLibraryAdapter
{
    public function fetch(): string
    {
        return (new FileDocuments)->fetch();
    }
}
