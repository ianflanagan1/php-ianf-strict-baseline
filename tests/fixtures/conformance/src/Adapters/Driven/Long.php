<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

final class Long
{
    /** @return list<int> */
    public function rows(): array
    {
        $rows = [];
        $rows[] = 0;
        $rows[] = 1;
        $rows[] = 2;
        $rows[] = 3;
        $rows[] = 4;
        $rows[] = 5;
        $rows[] = 6;
        $rows[] = 7;
        $rows[] = 8;
        $rows[] = 9;
        $rows[] = 10;
        $rows[] = 11;
        $rows[] = 12;
        $rows[] = 13;
        $rows[] = 14;
        $rows[] = 15;
        $rows[] = 16;
        $rows[] = 17;
        $rows[] = 18;
        $rows[] = 19;
        $rows[] = 20;
        $rows[] = 21;
        $rows[] = 22;
        $rows[] = 23;
        $rows[] = 24;
        $rows[] = 25;
        $rows[] = 26;
        $rows[] = 27;
        $rows[] = 28;
        $rows[] = 29;
        $rows[] = 30;
        $rows[] = 31;
        $rows[] = 32;
        $rows[] = 33;
        $rows[] = 34;
        $rows[] = 35;
        $rows[] = 36;
        $rows[] = 37;
        $rows[] = 38;
        $rows[] = 39;
        $rows[] = 40;
        $rows[] = 41;
        $rows[] = 42;
        $rows[] = 43;
        $rows[] = 44;
        $rows[] = 45;
        $rows[] = 46;
        $rows[] = 47;
        $rows[] = 48;
        $rows[] = 49;
        $rows[] = 50;
        $rows[] = 51;
        $rows[] = 52;
        $rows[] = 53;
        $rows[] = 54;

        return array_values(array_filter($rows, static fn (int $row): bool => $row % 2 === 0));
    }
}
