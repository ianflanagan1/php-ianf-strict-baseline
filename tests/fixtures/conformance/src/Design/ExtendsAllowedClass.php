<?php

declare(strict_types=1);

namespace App\Design;

use PHPUnit\Framework\TestCase;

final class NotFound extends \RuntimeException {}

final class ExtendsAllowedClass extends TestCase {}
