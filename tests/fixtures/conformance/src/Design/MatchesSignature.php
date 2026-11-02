<?php

declare(strict_types=1);

namespace App\Design;

final class MatchesSignature implements \JsonSerializable
{
    public function jsonSerialize(): mixed
    {
        return null;
    }
}

final class Overrides extends \RuntimeException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message);
    }
}
