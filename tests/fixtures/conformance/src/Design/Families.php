<?php

declare(strict_types=1);

namespace App\Design;

/** @phpstan-sealed Success|Failure */
interface Result {}

final class Success implements Result {}

final class Failure implements Result {}

enum Colour
{
    case Red;
    case Green;
    case Blue;
}

interface Shape {}
