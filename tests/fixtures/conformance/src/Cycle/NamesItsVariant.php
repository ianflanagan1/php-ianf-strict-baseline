<?php

declare(strict_types=1);

namespace App\Cycle;

/**
 * A sealed union that names one of its variants. The union and its variants count as one
 * unit-of-code, so the references between them are no cycle.
 *
 * @phpstan-sealed Circle|Square
 */
interface Outline
{
    public function within(Circle $circle): Outline;
}

final class Circle implements Outline
{
    #[\Override]
    public function within(Circle $circle): Outline
    {
        return $circle;
    }
}

final class Square implements Outline
{
    #[\Override]
    public function within(Circle $circle): Outline
    {
        return $this;
    }
}
