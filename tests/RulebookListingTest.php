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
    public function test_every_object_in_the_listing_holds_only_fields_the_rulebook_schema_allows(): void
    {
        $listing = json_decode(RulebookListing::json(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($listing);
        $this->assertSame([], array_diff(array_keys($listing), ['language', 'baseline', 'roles', 'checks', 'clauses']));
        $this->assertIsArray($listing['checks']);
        foreach ($listing['checks'] as $id => $check) {
            $this->assertIsArray($check);
            $this->assertSame([], array_diff(array_keys($check), ['kind', 'tool', 'summary']), "check {$id}");
        }
        $this->assertIsArray($listing['clauses']);
        $allowed = ['coverage', 'checks', 'proof', 'gap', 'where', 'intent', 'reason', 'overreach', 'note'];
        foreach ($listing['clauses'] as $id => $claim) {
            $this->assertIsArray($claim);
            $this->assertSame([], array_diff(array_keys($claim), $allowed), "clause {$id}");
        }
    }

    /**
     * @throws JsonException
     */
    public function test_every_check_kind_is_built_in_tool_or_custom(): void
    {
        $listing = json_decode(RulebookListing::json(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($listing);
        $this->assertIsArray($listing['checks']);
        foreach ($listing['checks'] as $id => $check) {
            $this->assertIsArray($check);
            $this->assertContains($check['kind'], ['built-in', 'tool', 'custom'], "check {$id}");
        }
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
