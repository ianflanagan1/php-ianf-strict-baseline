<?php

declare(strict_types=1);

namespace App\Design;

final class ExtendsAbstractClass extends AbstractBase
{
    #[\Override]
    public function name(): string
    {
        return 'name';
    }
}
