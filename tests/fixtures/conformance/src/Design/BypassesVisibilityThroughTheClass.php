<?php

declare(strict_types=1);

namespace App\Design;

/**
 * Reaches a private property and a private method without naming ReflectionProperty or
 * ReflectionMethod.
 */
final class BypassesVisibilityThroughTheClass
{
    /**
     * @throws \ReflectionException
     */
    public function read(object $order): mixed
    {
        return (new \ReflectionClass($order))->getProperty('total')->getValue($order);
    }

    /**
     * @throws \ReflectionException
     */
    public function call(object $order): mixed
    {
        return (new \ReflectionObject($order))->getMethod('total')->invoke($order);
    }
}
