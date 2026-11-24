<?php

declare(strict_types=1);

namespace App\Design;

/**
 * A field initialiser that reads the current time, which PHP refuses to compile.
 */
final class InitialisesAFieldWithACall
{
    public int $at = time();
}
