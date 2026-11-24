<?php

declare(strict_types=1);

namespace App\Design;

final class WalksACollection
{
    /**
     * @param  list<string>  $names
     */
    public function upper(array $names): int
    {
        array_walk($names, static function (string &$name): void {
            $name = strtoupper($name);
        });

        return count($names);
    }

    /**
     * @param  \Iterator<int, string>  $names
     */
    public function visited(\Iterator $names): int
    {
        return iterator_apply($names, static fn (): bool => true);
    }
}
