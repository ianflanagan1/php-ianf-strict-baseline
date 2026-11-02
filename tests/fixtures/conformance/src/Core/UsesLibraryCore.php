<?php

declare(strict_types=1);

namespace App\Core;

use IanFlanagan\SomeLibrary\Core\Money;

final class UsesLibraryCore
{
    public function cents(Money $money): int
    {
        return $money->minor;
    }
}
