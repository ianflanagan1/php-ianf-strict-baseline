<?php

declare(strict_types=1);

namespace App\Design;

final class BypassesVisibility
{
    /**
     * @throws \ReflectionException
     */
    public function read(object $order): mixed
    {
        return (new \ReflectionProperty($order, 'total'))->getValue($order);
    }

    /**
     * @throws \ReflectionException
     */
    public function call(object $order): mixed
    {
        return (new \ReflectionMethod($order, 'total'))->invoke($order);
    }

    public function bound(object $order): \Closure
    {
        return \Closure::bind(static fn (object $o): array => get_object_vars($o), null, $order::class);
    }

    public function boundTo(object $order): \Closure
    {
        return (static fn (object $o): array => get_object_vars($o))->bindTo(null, $order::class);
    }

    public function calledOn(object $order): mixed
    {
        return (fn (): array => get_object_vars($this))->call($order);
    }
}
