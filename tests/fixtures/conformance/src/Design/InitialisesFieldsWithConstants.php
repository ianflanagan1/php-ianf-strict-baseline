<?php

declare(strict_types=1);

namespace App\Design;

/**
 * Field initialisers that are literals and constants the code declares.
 */
final class InitialisesFieldsWithConstants
{
    private const int DOZEN = 12;

    public int $gross = self::DOZEN * self::DOZEN;

    public string $line = 'total'.PHP_EOL;

    public Colour $colour = Colour::Red;

    /** @var list<int> */
    public array $sizes = [1, 2, 3];
}
