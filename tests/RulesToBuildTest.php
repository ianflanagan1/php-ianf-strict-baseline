<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Tests;

use IanFlanagan\PhpIanfStrictBaseline\Core\RulebookListing;
use JsonException;
use PHPUnit\Framework\TestCase;

/**
 * Keeps docs/phpstan-rules-to-build.md, the list of PHPStan rules the baseline still needs, in
 * step with the listing and with the rules that exist.
 */
final class RulesToBuildTest extends TestCase
{
    private const string PATH = 'docs/phpstan-rules-to-build.md';

    private const string RULES_NAMESPACE = 'IanFlanagan\\PhpIanfStrictBaseline\\Adapters\\Driving\\PhpStan\\';

    /** What an entry says of its rule itself, when no other document specifies it. */
    private const array OWN_FIELDS = ['Reports', 'Allows', 'Fixtures', 'Why no existing tool'];

    public function test_the_list_has_entries(): void
    {
        $this->assertNotEmpty(self::entries(), self::PATH.' lists no rule; give each a "### <RuleName>" heading');
    }

    /**
     * @throws JsonException
     */
    public function test_every_entry_names_clauses_the_listing_does_not_cover_in_full(): void
    {
        $coverage = self::coverageByClause();
        foreach (self::entries() as $rule => $fields) {
            $this->assertArrayHasKey('Clauses', $fields, "{$rule} has no Clauses line");
            preg_match_all('/`([a-z0-9-]+)`/', $fields['Clauses'], $clauses);
            $this->assertNotEmpty($clauses[1], "{$rule} names no clause");
            foreach ($clauses[1] as $clause) {
                $this->assertArrayHasKey($clause, $coverage, "{$rule} names {$clause}, which the listing doesn't have");
                $this->assertNotSame('full', $coverage[$clause], "{$rule} names {$clause}, which the listing covers in full; drop the clause from the entry");
            }
        }
    }

    public function test_every_entry_says_what_its_rule_must_do_or_where_that_is_specified(): void
    {
        foreach (self::entries() as $rule => $fields) {
            if (isset($fields['Specified in'])) {
                continue;
            }
            foreach (self::OWN_FIELDS as $field) {
                $this->assertArrayHasKey($field, $fields, "{$rule} has no {$field} line, and no Specified in line in its place");
            }
        }
    }

    public function test_no_entry_names_a_rule_that_exists(): void
    {
        foreach (array_keys(self::entries()) as $rule) {
            $this->assertFalse(class_exists(self::RULES_NAMESPACE.$rule), "{$rule} exists; delete its entry from ".self::PATH);
        }
    }

    /**
     * Each entry's fields, by its rule's name: the text after "- **<Field>:**" on its line.
     *
     * @return array<string, array<string, string>>
     */
    private static function entries(): array
    {
        $sections = preg_split('/^### (\w+)$/m', self::contents(), -1, PREG_SPLIT_DELIM_CAPTURE);
        self::assertIsArray($sections);
        $entries = [];
        foreach (array_chunk(array_slice($sections, 1), 2) as [$rule, $body]) {
            self::assertArrayNotHasKey($rule, $entries, "{$rule} has two entries");
            preg_match_all('/^- \*\*([^*]+):\*\* (.+)$/m', $body, $lines, PREG_SET_ORDER);
            $entries[$rule] = array_column($lines, 2, 1);
        }

        return $entries;
    }

    /**
     * @return array<string, string>
     *
     * @throws JsonException
     */
    private static function coverageByClause(): array
    {
        $listing = json_decode(RulebookListing::json(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($listing);
        self::assertIsArray($listing['clauses']);
        $coverage = [];
        foreach ($listing['clauses'] as $clause => $claim) {
            self::assertIsString($clause);
            self::assertIsArray($claim);
            self::assertIsString($claim['coverage']);
            $coverage[$clause] = $claim['coverage'];
        }

        return $coverage;
    }

    private static function contents(): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/'.self::PATH);
        self::assertIsString($contents, self::PATH.' is missing');

        return $contents;
    }
}
