<?php

declare(strict_types=1);

namespace App\Adapters\Driven;

use App\Core\Pure;

/**
 * Has the database driver build a core type from a row, without its factory method.
 */
final class FillsObjectsFromRows
{
    public function fetched(\PDOStatement $rows): object|false
    {
        return $rows->fetchObject(Pure::class);
    }

    public function fromMysql(\mysqli_result $rows): object|false|null
    {
        return $rows->fetch_object(Pure::class);
    }

    public function fromMysqlFunction(\mysqli_result $rows): object|false|null
    {
        return mysqli_fetch_object($rows, Pure::class);
    }
}
