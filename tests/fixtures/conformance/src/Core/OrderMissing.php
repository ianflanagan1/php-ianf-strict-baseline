<?php

declare(strict_types=1);

namespace App\Core;

/**
 * An exception for an expected failure, which is a checked exception: it extends neither
 * LogicException nor RuntimeException.
 */
final class OrderMissing extends \Exception {}
