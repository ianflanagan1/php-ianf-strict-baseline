<?php

declare(strict_types=1);

namespace Acme\Toolkit;

/**
 * Pure: every call it makes, at any depth, is to a function the bans don't list.
 */
final class Slug
{
    public static function of(string $text): string
    {
        return trim(self::collapse(strtolower($text)), '-');
    }

    private static function collapse(string $text): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '-', $text);
    }
}
