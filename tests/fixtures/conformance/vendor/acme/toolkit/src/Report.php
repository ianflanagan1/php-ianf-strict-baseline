<?php

declare(strict_types=1);

namespace Acme\Toolkit;

/**
 * Calls a method on an interface value it builds itself. The writer it builds is pure, but the
 * check follows the interface's type, and any implementation of it could answer.
 */
final class Report
{
    public static function render(string $text): string
    {
        return self::writer()->write($text);
    }

    private static function writer(): Writer
    {
        return new UpperWriter;
    }
}
