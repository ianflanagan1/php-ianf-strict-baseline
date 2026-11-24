<?php

declare(strict_types=1);

namespace App\Design;

/**
 * A field initialiser that builds an object, which PHP refuses to compile.
 */
final class InitialisesAFieldWithAnObject
{
    /** @var \ArrayObject<string, int> */
    public \ArrayObject $map = new \ArrayObject;
}
