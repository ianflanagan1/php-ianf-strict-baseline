<?php

declare(strict_types=1);

namespace IanFlanagan\PhpBaseline\Core;

/**
 * How php-baseline enforces each clause of the code rulebook
 * (github.com/ianflanagan1/code-rulebook), as the coverage listing the rulebook checks.
 *
 * COUPLED:coverage-listing-shape. = code-rulebook:schema/coverage.schema.json (fields, check kinds and coverage levels)
 */
final class RulebookListing
{
    public const string PATH = 'rulebook-coverage.json';

    public static function json(): string
    {
        return json_encode(self::listing(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * @return array<string, mixed>
     */
    private static function listing(): array
    {
        return [
            'language' => 'php',
            'baseline' => 'ianflanagan1/php-baseline',
            'roles' => [
                'core' => 'Files under src/Core/, and classes of my dependencies in a \\Core\\ namespace.',
                'port' => 'Files under src/Ports/, and classes of my dependencies in a \\Ports\\ namespace.',
                'orchestrator' => 'Every other file under src/, and every other class of my dependencies but tests.',
                'driving-adapter' => 'Files under src/Adapters/Driving/, and classes of my dependencies in an \\Adapters\\Driving\\ namespace.',
                'driven-adapter' => 'Files under src/Adapters/Driven/, and classes of my dependencies in an \\Adapters\\Driven\\ namespace.',
                'entrypoint' => 'Scripts in bin/ and public/.',
            ],
            'checks' => [
                'deptrac-layers' => [
                    'kind' => 'tool',
                    'tool' => 'deptrac',
                    'summary' => 'Layers by directory: src/Core/ is the core, src/Ports/ the ports, src/Adapters/Driving/ and src/Adapters/Driven/ the two kinds of adapter, and the rest of src/ the orchestrators. A dependency of mine, in IanFlanagan\\<Project>\\, is layered by the same names in its namespace. The core depends on nothing else, ports only on the core, orchestrators and driven adapters only on the core and ports, and driving adapters only on the core and orchestrators.',
                ],
                'scripts-unimportable' => [
                    'kind' => 'language',
                    'summary' => 'An entrypoint is a script in bin/ or public/, not a class, so no class can depend on it.',
                ],
                'disallowed-effects' => [
                    'kind' => 'tool',
                    'tool' => 'phpstan',
                    'summary' => 'spaze/phpstan-disallowed-calls bans the functions, classes, constants and superglobals that perform each effect, and the global keyword, outside src/Adapters/Driving/, src/Adapters/Driven/, bin/, public/ and tests/.',
                ],
                'disallowed-configuration' => [
                    'kind' => 'tool',
                    'tool' => 'phpstan',
                    'summary' => 'spaze/phpstan-disallowed-calls bans getenv(), putenv(), ini_get(), ini_set(), $_ENV and $_SERVER outside bin/, public/ and tests/.',
                ],
                'disallowed-containers' => self::phpstan('spaze/phpstan-disallowed-calls bans the common dependency-injection containers everywhere: PSR-11, PHP-DI, Symfony, Laravel, League, Laminas, Nette and Pimple.'),
                'static-state' => self::rule('StaticStateRule: no static property or static variable outside src/Adapters/Driving/, src/Adapters/Driven/, bin/, public/ and tests/.'),
                'function-length' => self::rule('FunctionLengthRule: each function, method and closure in src/Adapters/Driving/, src/Adapters/Driven/, bin/ and public/ is at most 50 lines.'),
                'script-length' => self::rule("ScriptLengthRule: a script's top-level code in bin/ and public/ is at most 50 lines."),
                'dependency-cycles' => self::rule('DependencyCycleRule: no class, interface, trait or enum depends on itself through others.'),
                'phpstan-sealed' => self::phpstan('PHPStan reports a class that extends or implements a type sealed with a phpstan-sealed tag that does not list it.'),
                'phpstan-match' => self::phpstan('PHPStan, from level 4, which config/phpstan.neon sets, reports a match that leaves out a case of an enum or a sealed type.'),
                'switch-on-closed-family' => self::rule('SwitchOnClosedFamilyRule: no switch on an enum or a sealed type, so every branch on one is a match.'),
                'match-default-arm' => self::rule('MatchDefaultArmRule: a match on an enum or a sealed type has no default arm.'),
                'inheritance' => self::rule("InheritanceRule: a class extends only a class phpBaselineAllowedParentClasses names, PHPUnit's TestCase by default, or one of PHP's own exceptions."),
                'parameter-default' => self::rule('ParameterDefaultRule: no parameter has a default value, except in a method that overrides or implements another.'),
                'no-discard' => self::phpstan('PHPStan reports a call to a function or method marked #[\\NoDiscard] whose result is dropped.'),
            ],
            'clauses' => [
                'every-unit-has-one-role' => self::full(['deptrac-layers'], 'test_every_unit_has_one_role') + ['note' => 'A file under src/Adapters/ in neither Driving/ nor Driven/ is an orchestrator, so the effects it performs are reported.'],
                'dependency-units-keep-their-role' => self::partial(['deptrac-layers'], 'Only a dependency of mine, in IanFlanagan\\<Project>\\, has roles: any other library is in no layer, so any role may use it.', 'test_a_dependency_of_mine_has_the_roles_its_namespace_gives'),
                'applications-are-never-dependencies' => self::none(),
                'library-adapters-use-only-the-standard-library' => self::noneBecause('deptrac leaves out classes in no layer, so an adapter can use any library.'),
                'core-imports-only-core' => self::full(['deptrac-layers'], 'test_core_imports_only_core'),
                'core-performs-no-effects' => self::partial(['disallowed-effects', 'disallowed-configuration', 'static-state'], self::effectsGap(), 'test_core_performs_no_effects'),
                'core-libraries-are-pure' => self::noneBecause('deptrac leaves out classes in no layer, so the core can use any library.'),
                'core-receives-only-pure-values' => self::none(),
                'orchestrator-imports-no-adapter' => self::full(['deptrac-layers', 'scripts-unimportable'], 'test_orchestrator_imports_no_adapter'),
                'orchestrator-performs-no-effects' => self::partial(['disallowed-effects', 'disallowed-configuration', 'static-state'], self::effectsGap(), 'test_orchestrator_performs_no_effects'),
                'orchestrator-libraries-are-pure' => self::noneBecause('deptrac leaves out classes in no layer, so an orchestrator can use any library.'),
                'orchestrator-branches-only-on-core-results' => self::none(),
                'ports-declared-by-their-user' => self::full(['deptrac-layers'], 'test_ports_are_shared_by_orchestrators_and_driven_adapters_and_import_only_the_core') + ['note' => 'A PHP driven adapter has to name the interface it implements, so ports are a layer of their own, in src/Ports/: orchestrators and driven adapters may import it, and it may import only the core. No adapter can declare a port, since no orchestrator may import an adapter.'],
                'port-units-follow-typing' => ['case' => 'nominal'] + self::partial(['deptrac-layers'], 'An interface an orchestrator declares in its own layer is caught only once a driven adapter implements it.', 'test_driven_adapter_imports_only_core_ports_and_driven_adapters'),
                'ports-named-in-domain-terms' => ['coverage' => 'convention', 'where' => 'README.md, Roles'],
                'driving-adapter-imports-only-core-orchestrators-and-driving-adapters' => self::full(['deptrac-layers', 'scripts-unimportable'], 'test_driving_adapter_imports_only_core_orchestrators_and_driving_adapters'),
                'driven-adapter-imports-only-core-ports-and-driven-adapters' => self::full(['deptrac-layers', 'scripts-unimportable'], 'test_driven_adapter_imports_only_core_ports_and_driven_adapters'),
                'driving-adapter-calls-no-driven-adapter' => self::partial(['deptrac-layers'], 'A driving adapter can import neither a driven adapter nor a port, and a driven adapter can implement no interface a driving adapter declares, but one given a callable, or an interface from a library outside my namespace, is not caught.', 'test_driving_adapter_imports_only_core_orchestrators_and_driving_adapters'),
                'driven-adapter-calls-no-orchestrator' => self::partial(['deptrac-layers'], 'A driven adapter given, through a port, an orchestrator that implements that port is not caught; nor is one given a callable.', 'test_driven_adapter_imports_only_core_ports_and_driven_adapters'),
                'adapter-size-is-limited' => self::full(['function-length'], 'test_adapter_size_is_limited') + ['note' => 'Length only, not complexity.'],
                'only-entrypoints-read-configuration' => self::full(['disallowed-configuration'], 'test_only_entrypoints_read_configuration'),
                'entrypoint-size-is-limited' => self::full(['function-length', 'script-length'], 'test_entrypoint_size_is_limited'),
                'entrypoint-calls-only-driving-adapters' => self::none(),
                'wired-without-a-container' => self::partial(['disallowed-containers'], 'Only the common container libraries are banned, not a service locator written in the project.', 'test_wired_without_a_container'),
                'no-import-cycles' => self::full(['dependency-cycles'], 'test_no_import_cycles') + ['note' => 'The units are classes, interfaces, traits and enums.'],
                'closed-families-are-sealed' => self::partial(['phpstan-sealed'], 'Only a type with a phpstan-sealed tag is checked. An interface without one, which any class can implement, passes.', 'test_closed_families_are_sealed'),
                'matches-on-closed-families-are-exhaustive' => self::full(['phpstan-match', 'switch-on-closed-family'], 'test_matches_on_closed_families_are_exhaustive') + ['note' => 'A project that sets a PHPStan level below 4 loses the check.'],
                'matches-on-closed-families-have-no-default-arm' => self::full(['match-default-arm'], 'test_matches_on_closed_families_have_no_default_arm') + ['note' => 'PHP needs no catch-all: a match that handles no arm throws UnhandledMatchError.'],
                'classes-extend-only-allowed-bases' => self::full(['inheritance'], 'test_classes_extend_only_allowed_bases') + ['note' => "PHP's own exceptions may be extended, since a throwable class can't be written any other way."],
                'no-parameter-default-values' => self::full(['parameter-default'], 'test_no_parameter_default_values'),
                'no-optional-parameters' => self::full(['parameter-default'], 'test_no_parameter_default_values') + ['note' => 'A PHP parameter is optional only through a default value, or by being variadic, which is not checked.'],
                'returned-failures-are-used' => [
                    'coverage' => 'partial',
                    'checks' => ['no-discard'],
                    'gap' => 'Only a function or method marked #[\\NoDiscard] is checked, and a result assigned to a variable that is never read passes.',
                    'proof' => [self::conformance('test_returned_failures_are_used'), self::conformance('test_returned_failures_are_used_misses_unmarked_functions')],
                ],
                'check-fails-on-unformatted-files' => ['coverage' => 'convention', 'where' => 'README.md, Use: the check task runs vendor/bin/pint --test'],
                'formatter-settings-come-from-the-baseline' => ['coverage' => 'convention', 'where' => "README.md, Use: pint.json extends the baseline's config/pint.json"],
                'sealed-variants-share-their-unit' => self::none(),
                'open-interfaces-are-not-matched-by-type' => self::none(),
                'ports-are-open' => self::none(),
                'constructors-only-store-their-arguments' => self::none(),
                'field-initialisers-are-constants' => self::none(),
                'invariant-types-are-closed' => self::none(),
                'closed-types-have-no-way-around-their-factories' => self::none(),
                'arguments-are-not-mutated' => self::none(),
                'fields-are-declared-read-only' => ['case' => 'supported'] + self::none(),
                'fields-are-assigned-only-at-construction' => self::none(),
                'field-collections-are-not-changed' => self::none(),
                'parameters-are-not-reassigned' => self::none(),
                'no-reference-parameters' => ['case' => 'supported'] + self::none(),
                'core-locals-are-not-reassigned-once-read' => ['case' => 'unsupported'] + self::none(),
            ],
        ];
    }

    private static function effectsGap(): string
    {
        return "The bans list the common functions and classes for each effect, not every one, and keeping state that changes across calls in an instance's fields (held-state) isn't detected.";
    }

    /**
     * @return array<string, string>
     */
    private static function phpstan(string $summary): array
    {
        return ['kind' => 'tool', 'tool' => 'phpstan', 'summary' => $summary];
    }

    /**
     * A PHPStan rule of the baseline's own, in src/Adapters/Driving/PhpStan/.
     *
     * @return array<string, string>
     */
    private static function rule(string $summary): array
    {
        return ['kind' => 'custom', 'summary' => $summary];
    }

    /**
     * @param  list<string>  $checks
     * @return array<string, mixed>
     */
    private static function full(array $checks, string $test): array
    {
        return ['coverage' => 'full', 'checks' => $checks, 'proof' => [self::conformance($test)]];
    }

    /**
     * @param  list<string>  $checks
     * @return array<string, mixed>
     */
    private static function partial(array $checks, string $gap, string $test): array
    {
        return ['coverage' => 'partial', 'checks' => $checks, 'gap' => $gap, 'proof' => [self::conformance($test)]];
    }

    /**
     * @return array<string, string>
     */
    private static function none(): array
    {
        return ['coverage' => 'none'];
    }

    /**
     * @return array<string, string>
     */
    private static function noneBecause(string $note): array
    {
        return ['coverage' => 'none', 'note' => $note];
    }

    private static function conformance(string $test): string
    {
        return 'tests/ConformanceTest.php: '.$test;
    }
}
