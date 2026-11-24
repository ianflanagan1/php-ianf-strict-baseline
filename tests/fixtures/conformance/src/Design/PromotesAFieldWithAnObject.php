<?php

declare(strict_types=1);

namespace App\Design;

/**
 * A promoted property whose default builds an object, which PHP compiles: it is a parameter's
 * default.
 */
final class PromotesAFieldWithAnObject
{
    /**
     * @param  \ArrayObject<string, int>  $map
     */
    public function __construct(public \ArrayObject $map = new \ArrayObject) {}
}
