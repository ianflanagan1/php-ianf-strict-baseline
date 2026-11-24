<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Tests;

use IanFlanagan\PhpIanfStrictBaseline\Core\RulebookListing;
use JsonException;
use PHPUnit\Framework\TestCase;

final class RulebookListingTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function test_the_committed_listing_is_current(): void
    {
        $this->assertSame(
            file_get_contents(dirname(__DIR__).'/'.RulebookListing::PATH),
            RulebookListing::json(),
            RulebookListing::PATH.' is out of date; run `devbox run -- task rulebook-coverage`',
        );
    }

    /**
     * @throws JsonException
     */
    public function test_every_proof_names_a_test_that_exists(): void
    {
        preg_match_all('#"tests/([\w/]+)\.php: (\w+)"#', RulebookListing::json(), $proofs, PREG_SET_ORDER);
        $this->assertNotEmpty($proofs);
        foreach ($proofs as [, $path, $method]) {
            $class = __NAMESPACE__.'\\'.str_replace('/', '\\', $path);
            $this->assertTrue(method_exists($class, $method), "tests/{$path}.php has no test {$method}");
        }
    }
}
