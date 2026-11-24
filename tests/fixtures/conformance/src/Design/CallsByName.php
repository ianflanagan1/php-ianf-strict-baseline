<?php

declare(strict_types=1);

namespace App\Design;

final class CallsByName
{
    public function trimmed(string $text): mixed
    {
        return call_user_func('trim', $text);
    }

    /**
     * @param  list<string>  $words
     */
    public function joined(array $words): mixed
    {
        return call_user_func_array('implode', [',', $words]);
    }
}
