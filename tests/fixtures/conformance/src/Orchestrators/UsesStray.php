<?php

declare(strict_types=1);

namespace App\Orchestrators;

use App\App\Stray;

final class UsesStray
{
    public function __construct(private readonly Stray $stray) {}
}
