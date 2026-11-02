<?php

declare(strict_types=1);

namespace App\App;

use App\Core\Pure;

/**
 * Under src/ but in none of the roles' directories, so it may depend on nothing.
 */
final class Stray
{
    public function shout(Pure $pure, string $s): string
    {
        return $pure->upper($s);
    }
}
