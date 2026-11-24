<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Core\Reach;

/**
 * One thing a function, a method or a file's load-time code does that bears on whether the core
 * may reach it: a call to code to follow, a file it loads, an impure operation it performs, or a
 * call the check can't resolve.
 *
 * @phpstan-sealed Calls|Loads|Performs|CannotResolve
 */
interface Step
{
}
