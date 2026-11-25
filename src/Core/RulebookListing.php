<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Core;

use JsonException;

/**
 * How php-ianf-strict-baseline enforces each clause of the code rulebook
 * (github.com/ianflanagan1/code-rulebook), as the coverage listing the rulebook checks.
 *
 * COUPLED:coverage-listing-schema:[same-shape; //github.com/ianflanagan1/code-rulebook/schema/coverage.schema.json; //github.com/ianflanagan1/code-rulebook/internal/core/rulebook/model.go;
 * //github.com/ianflanagan1/go-ianf-strict-baseline/internal/core/rulebook/rulebook.go;
 * //github.com/ianflanagan1/ts-ianf-strict-baseline/src/core/listing.ts] fields, check kinds and coverage levels
 */
final class RulebookListing
{
    public const string PATH = 'rulebook-coverage.json';

    private const string NO_HELPERS = 'The checks tell no helper class from a member: every class there counts as a member, and a helper is a private method.';

    private const string NO_DECISIONS = "The checks don't tell a decision from a value: both are the one Core layer.";

    private const string NO_SYSTEM_ADAPTERS = "The clause's exception for a system adapter isn't made. PHP's shared code holds no system adapters yet, so there is no system stopwatch or system deadlines to name, and deptrac would report a driving adapter that references one as referencing a driven adapter.";

    private const string LEVEL_CAN_BE_LOWERED = 'A project that sets a lower PHPStan level loses the check, which type-checker-runs-at-its-strictest is for.';

    private const string REPORTS_NOT_DECIDABLE = 'What a driving adapter writes to its caller for each failure, a failure or a success, is not decidable statically. An exhaustive match makes it handle each one, not report each as a failure.';

    private const string CORE = 'By directory, not from the code: files under src/Core/, and classes of my dependencies in a \\Core\\ namespace. The checks don\'t tell a decision from a value: both are the one Core layer, so nothing that turns on the difference between them is checked. It has no helpers.';

    /**
     * @throws JsonException never: the listing holds only strings, and arrays of them
     */
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
            'baseline' => 'ianflanagan1/php-ianf-strict-baseline',
            'roles' => [
                'entrypoint' => 'By directory, not from the code: each script in bin/ and public/ is an entrypoint, whether or not it has statements outside its declarations. Its helpers are the functions it declares; a class only entrypoints use has no directory, and the checks tell none.',
                'driving-adapter' => 'By directory, not from the code: files under src/Adapters/Driving/, and classes of my dependencies in an \\Adapters\\Driving\\ namespace. The checks tell neither a driving adapter\'s entry from the helpers that serve its contract nor one driving adapter from another: every class there counts as a member.',
                'driven-adapter' => 'By directory, not from the code: files under src/Adapters/Driven/, and classes of my dependencies in an \\Adapters\\Driven\\ namespace, whether or not a class there implements a port. '.self::NO_HELPERS,
                'port' => 'By directory, not from the code: files under src/Ports/, and classes of my dependencies in a \\Ports\\ namespace, whether or not a file there declares an interface that a service holds. It has no helpers.',
                'orchestrator' => 'By directory, not from the code: files under src/Orchestrators/, and classes of my dependencies in an \\Orchestrators\\ namespace, whether or not a class there holds a port. '.self::NO_HELPERS,
                'decision' => self::CORE,
                'value' => self::CORE,
                'fake' => 'By directory, not from the code: files under src/Fakes/, and classes of my dependencies in a \\Fakes\\ namespace, whether or not a class there implements a port. Each is named Fake and its port\'s name, which nothing checks. Like any class under src/ outside the adapters\' directories, it may perform no impure operation the bans list. A port\'s implementation under tests/ is treated as test code. '.self::NO_HELPERS,
            ],
            'checks' => [
                'deptrac-layers' => [
                    'kind' => 'tool',
                    'tool' => 'deptrac',
                    'summary' => "Layers by directory, which is how the checks tell the roles: src/Core/ is the core, decision and value code alike, src/Ports/ the ports, src/Orchestrators/ orchestrator code, and src/Adapters/Driving/ and src/Adapters/Driven/ the two adapters' code. src/Fakes/ holds the fakes, which reference only the core and ports, and src/Testing/ the test code a project ships for the tests of projects that require it, such as an assertion, which references only the core, ports and fakes; no role's code references either. Any other class under src/ is Unplaced, which may reference nothing and which nothing may reference. A dependency of mine, in IanFlanagan\\<Project>\\, is layered by the same names in its namespace. The core references nothing else, ports only the core, orchestrators and driven adapters only the core and ports, and driving adapters only the core and orchestrators. Every reference counts: an import, a qualified name and a name in the same namespace.",
                ],
                'scripts-unimportable' => [
                    'kind' => 'built-in',
                    'summary' => 'An entrypoint is a script in bin/ or public/, not a class, so no class can reference it.',
                ],
                'disallowed-effects' => [
                    'kind' => 'tool',
                    'tool' => 'phpstan',
                    'summary' => "spaze/phpstan-disallowed-calls bans the functions, classes, constants and superglobals that perform each impure operation outside the directories of the roles that may perform it, with a list of those directories for each operation: files, processes, the console and global logging outside src/Adapters/Driving/, src/Adapters/Driven/, bin/ and public/; network calls and databases outside src/Adapters/Driven/; serving requests, the request superglobals among them, outside src/Adapters/Driving/; the clock and randomness outside both adapters' directories, bin/ and public/; and the global keyword and \$GLOBALS everywhere. tests/ is in every list.",
                ],
                'disallowed-configuration' => [
                    'kind' => 'tool',
                    'tool' => 'phpstan',
                    'summary' => 'spaze/phpstan-disallowed-calls bans getenv(), putenv(), ini_get(), ini_set(), $_ENV and $_SERVER outside bin/, public/ and tests/.',
                ],
                'disallowed-sleeping' => self::phpstan('spaze/phpstan-disallowed-calls bans sleep(), usleep(), time_nanosleep() and time_sleep_until() in every directory, tests/ included.'),
                'disallowed-eval' => self::phpstan('spaze/phpstan-disallowed-calls bans eval() everywhere.'),
                'disallowed-calls-by-name' => self::phpstan('spaze/phpstan-disallowed-calls bans call_user_func() and call_user_func_array() everywhere.'),
                'disallowed-visibility-bypasses' => self::phpstan('spaze/phpstan-disallowed-calls bans, everywhere, every method of ReflectionProperty and ReflectionMethod, their constructors among them, so one that ReflectionClass built is reported where it is used; Closure::bind(), Closure::bindTo() and Closure::call(); and get_mangled_object_vars().'),
                'disallowed-assert' => self::phpstan('spaze/phpstan-disallowed-calls bans assert() everywhere.'),
                'disallowed-construction' => self::phpstan("spaze/phpstan-disallowed-calls bans, everywhere, the calls that have PHP build an object without its constructor or its factory method: ReflectionClass::newInstanceWithoutConstructor(), newLazyGhost() and newLazyProxy(); PDOStatement::fetchObject(), mysqli_result::fetch_object(), mysqli_fetch_object() and pg_fetch_object(); the constants PDO::FETCH_CLASS and PDO::FETCH_INTO; and unserialize() unless it is passed ['allowed_classes' => false]."),
                'disallowed-collection-walks' => self::phpstan('spaze/phpstan-disallowed-calls bans array_walk(), array_walk_recursive() and iterator_apply() everywhere.'),
                'disallowed-mocking' => self::phpstan("spaze/phpstan-disallowed-calls bans, in tests/ as everywhere, PHPUnit's createMock(), createStub(), getMockBuilder(), createPartialMock() and their configured and intersection forms, and every class of Mockery and Prophecy."),
                'disallowed-containers' => self::phpstan('spaze/phpstan-disallowed-calls bans the common dependency-injection containers everywhere: PSR-11, PHP-DI, Symfony, Laravel, League, Laminas, Nette and Pimple.'),
                'static-state' => self::rule('StaticStateRule: no static property or static variable outside tests/.'),
                'core-reach' => self::rule("CoreCallsReachNothingImpureRule: from each call, new and class constant or static property in src/Core/ whose target is code outside the core, wherever that code lives and whoever publishes it, follows every call the code can make, at any depth, in the versions installed, and the load-time code of every file that loads: the file's own top-level code, and the files its package's composer.json autoload files entry names. A call is resolved by name, by \$this, and by the type PHPStan gives the value it is made on. It reports, with the path from the core's call, an impure operation: an entry of the effect bans that has an allowIn list, the global keyword, a static variable or a write to a static property. It also reports a call it can't resolve: through a function value, or a class or method named by a value, that the code didn't receive as an argument; an interface's or an abstract method called on a value it didn't receive; eval(); an include of a path built at run time; a method a trait declares, whose code PHPStan doesn't walk outside the files it analyses; and a function or class it can't find."),
                'function-length' => self::rule('FunctionLengthRule: each function, method and closure in src/Adapters/Driving/, src/Adapters/Driven/, bin/ and public/ is at most 50 lines.'),
                'script-length' => self::rule("ScriptLengthRule: a script's top-level code in bin/ and public/ is at most 50 lines."),
                'dependency-cycles' => self::rule('DependencyCycleRule: no class, interface, trait or enum references itself through others, with a sealed type and its variants counted as one.'),
                'phpstan-sealed' => self::phpstan('PHPStan reports a class that extends or implements a type sealed with a phpstan-sealed tag that does not list it.'),
                'phpstan-match' => self::phpstan('PHPStan, from level 4, reports a match that leaves out a case of an enum or a sealed type. config/phpstan.neon sets level 10.'),
                'phpstan-missing-types' => self::phpstan('PHPStan, from level 6, reports a parameter, a return value or a property with no type, and an array or iterable with no value type. config/phpstan.neon sets level 10.'),
                'phpstan-mixed' => self::phpstan('PHPStan, from level 9, and from level 10 for a value whose type it had to assume, reports anything done with a mixed but testing it or passing it on: a call on it, an operation with it, a cast of it, or passing or returning it as a narrower type. config/phpstan.neon sets level 10.'),
                'switch-on-closed-family' => self::rule('SwitchOnClosedFamilyRule: no switch on an enum or a sealed type, so every branch on one is a match.'),
                'match-default-arm' => self::rule('MatchDefaultArmRule: a match on an enum or a sealed type has no default arm.'),
                'inheritance' => self::rule("InheritanceRule: a class extends only a class phpIanfStrictBaselineAllowedParentClasses names, PHPUnit's TestCase by default, or one of PHP's own exceptions."),
                'phpstan-checked-exceptions' => self::phpstan("PHPStan, with exceptions.check.missingCheckedExceptionInThrows, reports a function or method that throws a checked exception, or lets one escape from a call, with no @throws tag for it, and with exceptions.check.throwTypeCovariance, an implementation whose @throws tag names an exception that its interface's @throws tag doesn't. config/phpstan.neon turns both on, and leaves every exception checked but Error, LogicException, RuntimeException, Random\\RandomException and their subclasses."),
                'constant-property-defaults' => [
                    'kind' => 'built-in',
                    'summary' => "PHP compiles a property's default only when it is a constant expression: a call, a static call or a new there is a fatal error when the file is compiled.",
                ],
                'parameter-default' => self::rule('ParameterDefaultRule: no parameter has a default value, except in a method that overrides or implements another.'),
                'no-discard' => self::phpstan('PHPStan reports a call to a function or method marked #[\\NoDiscard] whose result is dropped.'),
                'verify-gitignore' => self::rule('php-ianf-strict-baseline verify reports a .gitignore without the line /cache/, or a project without one.'),
            ],
            'clauses' => self::architecture() + self::design() + self::errorHandling() + self::persistence() + self::formattingProjectAndTesting(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function architecture(): array
    {
        return [
            // adapters-and-entrypoints-reach-only-their-operations
            'adapters-and-entrypoints-perform-only-their-roles-operations' => [
                'coverage' => 'partial',
                'checks' => ['disallowed-effects', 'disallowed-configuration', 'static-state'],
                'gap' => "The clock and randomness are allowed in both adapters' directories and the entrypoints'. They are reserved for system adapters, which PHP's shared code doesn't hold yet, so a project's own driven adapter is still the only way to reach either, and the bans don't tell that adapter from other adapter and entrypoint code. A function that reads files makes a network call when it is given a URL, as file_get_contents('https://…') does, and is taken for a file read. State an entrypoint holds in an instance's fields isn't detected. The bans list the common functions and classes for each operation, not every one.",
                'proof' => [self::conformance('test_adapters_and_entrypoints_perform_only_their_roles_operations'), self::conformance('test_adapters_and_entrypoints_perform_only_their_roles_operations_misses_the_clock_and_urls_opened_as_files')],
            ] + self::planned(),
            'adapters-and-entrypoints-reach-only-their-roles-operations' => self::noneBecause('deptrac leaves out classes in no layer, and the baseline has no dependency list, so adapter and entrypoint code can use any library.'),

            // adapters-stay-thin
            'driving-adapter-code-references-only-orchestrators-and-the-core' => self::full(['deptrac-layers', 'scripts-unimportable'], 'test_driving_adapter_code_references_only_orchestrators_and_the_core') + ['note' => self::NO_SYSTEM_ADAPTERS],
            'driving-adapter-code-only-reports-what-a-decision-returns' => self::none(),
            'driven-adapter-code-references-only-ports-and-values' => self::partial(['deptrac-layers', 'scripts-unimportable'], self::NO_DECISIONS." A driven adapter that references or calls decision code isn't reported; one that references entrypoint, driving-adapter or orchestrator code is.", 'test_driven_adapter_code_references_only_ports_and_values', self::planned()),
            'driving-adapter-code-calls-no-driven-adapter' => self::partial(['deptrac-layers'], "A driving adapter can reference neither a driven adapter, a first-party dependency's among them, nor a port, and a driven adapter can implement no interface a driving adapter declares, but a driving adapter given a callable, or an interface from a library outside my namespace, is not caught.", 'test_driving_adapter_code_calls_no_driven_adapter', self::planned()) + ['note' => self::NO_SYSTEM_ADAPTERS],
            'driven-adapter-code-calls-no-orchestrator' => self::partial(['deptrac-layers'], 'A driven adapter given, through a port, an orchestrator that implements that port is not caught; nor is one given a callable.', 'test_driven_adapter_code_calls_no_orchestrator', self::planned()),
            'adapter-code-function-size-is-limited' => self::partial(['function-length'], "Only a function's length is limited, not its complexity.", 'test_adapter_code_function_size_is_limited', self::planned()),

            // core-is-pure
            'core-references-only-core' => self::full(['deptrac-layers', 'scripts-unimportable'], 'test_core_references_only_core'),
            'core-does-nothing-impure' => [
                'coverage' => 'partial',
                'checks' => ['disallowed-effects', 'disallowed-configuration', 'static-state'],
                'gap' => self::effectsGap()." Of the functions that read the machine's local time zone, date() and mktime() are banned, and getdate(), localtime() and DateTimeImmutable::createFromFormat() with no zone are not.",
                'proof' => [self::conformance('test_core_does_nothing_impure'), self::conformance('test_core_does_nothing_impure_misses_times_in_the_local_zone')],
            ] + self::planned(),
            'core-calls-reach-nothing-impure' => self::partial(
                ['core-reach'],
                "The impure operations are the effect bans' entries, which list the common functions and classes for each operation, not every one, so a call that reaches one they don't list, such as getdate(), passes, as does a function of a PHP extension they don't list. State kept in an instance's fields isn't detected. A method called on a value of a class is followed to that class's method, not to a subclass's override of it. A call on a parameter is taken for the caller's, so an object the followed code builds and passes to a parameter typed as an interface isn't followed to its methods, and a parameter reassigned before the call is still taken for the caller's value. The calls PHP makes itself, such as __toString() when an object is used as a string, __get() and __set(), aren't followed. It over-reports where it can't resolve a call that is pure: a template method calling an abstract method on \$this, a closure the code built and called itself, and every method a trait declares. That code the core calls is total, never throwing, isn't checked. What it reads is kept for the rest of a PHPStan process, and across runs only through PHPStan's result cache, which analyses every file again when composer.lock or vendor/composer/installed.json changes, not once for each package and version.",
                'test_core_calls_reach_nothing_impure',
                self::planned(),
            ),
            'core-loading-does-nothing-impure' => self::partial(
                ['disallowed-effects', 'disallowed-configuration', 'static-state', 'core-reach'],
                "A package's load-time code is checked when the core uses something in it, which loads that class's or function's file: the top-level code of every file that loads, and the files the package's autoload files entry names. A class the core only names in a type, which PHP doesn't load, isn't followed, and neither is the autoload files entry of a package the core uses nothing from, though Composer runs every installed package's entry when the autoloader is included. The bans list the common functions for each effect, not every one.",
                'test_core_loading_does_nothing_impure',
                self::planned(),
            ),

            // core-takes-values
            'core-receives-only-pure-values' => self::none(),

            // entrypoints-build-and-start
            'only-entrypoint-code-reads-configuration' => self::full(['disallowed-configuration'], 'test_only_entrypoint_code_reads_configuration'),
            'entrypoint-code-function-size-is-limited' => self::full(['function-length', 'script-length'], 'test_entrypoint_code_function_size_is_limited'),
            'entrypoint-code-wires-without-a-container' => self::partial(['disallowed-containers'], 'Only the common container libraries are banned, not a service locator written in the project.', 'test_entrypoint_code_wires_without_a_container', self::declined('A reference check sees only the libraries it lists, and a hand-written locator is an array looked up by name, which no static check tells from other arrays.')),
            'entrypoint-code-calls-only-driving-adapters-and-values' => self::noneBecause("bin/ and public/ are in no deptrac layer, and no check reads what an entrypoint calls. That the checks don't tell a decision from a value is only part of what is missing."),

            // every-declaration-has-one-role
            'every-declaration-has-one-role' => self::partial(['deptrac-layers'], "A role is taken from the path, which is a directory under src/ or a dependency's namespace, and not from the code: nothing reads a declaration's shape, or what it holds, implements and is built by, so a service that holds both a port and a database client takes its directory's role. A class under src/ in none of the roles' directories is reported only when it references a class in a layer, or one references it; one that does neither passes, and so does a function declared outside a class.", 'test_every_declaration_has_one_role', self::planned()),
            'a-unit-of-code-holds-one-role' => self::partial(['deptrac-layers'], "The unit-of-code is a class and its file, and a file takes one role, from its directory. A second declaration in a file is reported only when it references something that role may not, as an orchestrator declared beside its controller is when it takes a port. One that keeps to what its directory's role may reference isn't reported, such as a value object declared in an orchestrator's file, or a decision declared beside a core type, since both are the one Core layer.", 'test_a_unit_of_code_holds_one_role', self::planned()),
            'dependency-declarations-keep-their-role' => self::partial(['deptrac-layers'], "A first-party dependency's declaration takes its role from its namespace, as the project's take theirs from their directories, and not from its own code.", 'test_dependency_declarations_keep_their_role', self::planned()),
            'other-roles-reach-only-members' => self::none(),

            // internal-values-stay-inside
            'outside-facing-code-names-no-internal-type' => self::none(),
            'external-driving-adapters-get-no-internal-values' => self::none(),
            'external-driving-adapters-get-no-internal-values-from-decisions' => self::none(),
            'external-driven-adapters-take-no-internal-values' => self::none(),
            'only-decisions-and-internal-adapters-read-internal-values' => self::none(),
            'the-internal-wrapper-shows-nothing-it-holds' => self::noneBecause("PHP's shared ID unit-of-code, which would hold the Internal wrapper and the tests of what it shows, doesn't exist yet."),

            // no-reference-cycles
            'no-reference-cycles' => self::full(['dependency-cycles'], 'test_no_reference_cycles') + ['note' => 'The units are classes, interfaces, traits and enums, with a sealed type and its variants counted as one.'],

            // nothing-sleeps
            'nothing-sleeps' => self::partial(['disallowed-sleeping'], "Only the four sleep functions are reported. Waiting on a stream, a signal or a timer with nothing else to wait for, such as stream_select() given no streams and a timeout, isn't, and neither is a third-party function that sleeps.", 'test_nothing_sleeps', self::planned()),

            // orchestrators-act-the-core-decides
            'orchestrator-code-branches-only-on-decisions' => self::none(),
            'orchestrator-code-runs-no-loop-until-stopped' => self::none(),

            // orchestrators-reach-outside-through-ports
            'orchestrator-code-references-no-entrypoint-or-adapter-code' => self::full(['deptrac-layers', 'scripts-unimportable'], 'test_orchestrator_code_references_no_entrypoint_or_adapter_code'),
            'orchestrator-code-performs-no-effects' => self::partial(['disallowed-effects', 'disallowed-configuration', 'static-state'], self::effectsGap(), 'test_orchestrator_code_performs_no_effects', self::planned()),
            'orchestrator-code-reaches-nothing-impure' => self::noneBecause('deptrac leaves out classes in no layer, so an orchestrator can use any library.'),

            // ports-and-orchestrators-take-signature-types
            'port-methods-take-and-return-signature-types' => self::none(),
            'orchestrator-takes-and-returns-signature-types' => self::none(),
            'decisions-driving-adapters-call-take-and-return-signature-types' => self::none(),
            'orchestrator-is-given-ports' => [
                'coverage' => 'partial',
                'checks' => ['deptrac-layers', 'disallowed-containers'],
                'gap' => "Another vendor's class is in no layer, so an orchestrator built with a third-party client or a PSR-3 logger, or with an interface it didn't declare, isn't reported.",
                'proof' => [self::conformance('test_orchestrator_code_references_no_entrypoint_or_adapter_code'), self::conformance('test_entrypoint_code_wires_without_a_container')],
            ] + self::planned(),

            // ports-belong-to-their-callers
            'port-is-declared-by-its-caller' => self::full(['deptrac-layers'], 'test_port_is_declared_by_its_caller') + ['note' => 'A PHP driven adapter has to name the interface it implements, so ports are a layer of their own, in src/Ports/: orchestrators and driven adapters may reference it, and it may reference only the core. No adapter can declare a port, since no orchestrator may reference an adapter.'],
            'port-units-of-code-follow-interface-implementation' => self::partial(['deptrac-layers'], "An interface declared in an orchestrator's file is reported only once a driven adapter implements it, as a driven adapter that references an orchestrator.", 'test_port_units_of_code_follow_interface_implementation', self::planned()) + ['note' => 'The checks ask for more than the clause does. They tell a port by its directory, so an interface in a file of its own under src/Orchestrators/ is taken as orchestrator code, and a driven adapter that implements it is reported too.'],
            'port-is-named-in-domain-terms' => self::convention('README.md, Roles', self::declined("Whether a name is in the domain's terms, or a method is shaped for one thing its caller does, is a judgement no static check can make.")),

            // ports-have-no-call-order
            'port-methods-return-no-interfaces' => self::none(),
            'port-methods-need-no-call-order' => self::none(),
            'writes-that-belong-together-are-one-port-call' => self::noneDeclined("Which writes a use case needs made together isn't in the code, so no static check tells two calls that must both happen from two that needn't."),

            // reserved-operations-come-only-from-system-adapters
            'reserved-operations-are-performed-only-by-their-system-adapters' => self::noneBecause("disallowed-effects bans the clock and randomness functions in core, port and orchestrator code, which core-does-nothing-impure and orchestrator-code-performs-no-effects record. In adapter and entrypoint code, where this clause's violations are, it allows both, since PHP's shared code doesn't hold the system adapters yet."),
            'no-third-party-feature-performs-a-reserved-operation' => self::none(),
            'standard-library-and-third-party-code-takes-sources-from-the-system-adapters' => self::none(),

            // values-decide-nothing
            'value-code-references-only-value-code' => self::partial(['deptrac-layers'], self::NO_DECISIONS." A core type that references decision code isn't reported; one that references code of any other role is.", 'test_value_code_references_only_value_code', self::planned()),
            'value-code-decides-nothing' => self::noneDeclined('Whether a method derives a value by a rule that is part of what the value is, or answers a question a use case asks of it, is a judgement about what the code means, which no static check can make.'),
            'ports-reference-only-value-code' => self::partial(['deptrac-layers'], self::NO_DECISIONS." A port whose method takes a decision service, or returns an interface declared in decision code, isn't reported; one that references code of any other role is.", 'test_ports_reference_only_value_code', self::planned()),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function design(): array
    {
        return [
            // arguments-are-not-mutated
            'arguments-are-not-mutated' => self::none(),

            // closed-families-match-exhaustively
            'closed-families-are-sealed' => self::partial(['phpstan-sealed'], 'Only a type with a phpstan-sealed tag is checked. An interface without one, which any class can implement, passes.', 'test_closed_families_are_sealed', self::declined("Whether an interface's variants are all known to it is a design decision, which no static check can see until the type is sealed.")),
            'sealed-variants-share-their-unit-of-code' => self::none(),
            'matches-on-closed-families-are-exhaustive' => self::full(['phpstan-match', 'switch-on-closed-family'], 'test_matches_on_closed_families_are_exhaustive') + ['note' => 'A project that sets a PHPStan level below 4 loses the check, which type-checker-runs-at-its-strictest is for.'],
            'matches-on-closed-families-have-no-default-arm' => self::full(['match-default-arm'], 'test_matches_on_closed_families_have_no_default_arm') + ['note' => "A PHP match needs no statement to end it: one that handles no arm throws UnhandledMatchError. So the clause's exception never applies, and every default arm is reported, one that only throws among them."],

            // code-reaches-only-what-it-names
            'no-code-is-run-from-text' => self::full(['disallowed-eval'], 'test_no_code_is_run_from_text') + ['note' => "eval() is PHP 8's only way to run text as code: create_function(), assert() on a string and the e modifier of preg_replace() and mb_ereg_replace() are gone. An include of a path built at run time is nothing-is-imported-by-a-computed-path's."],
            'nothing-is-called-by-a-computed-name' => [
                'coverage' => 'partial',
                'checks' => ['disallowed-calls-by-name'],
                'gap' => "Only call_user_func() and call_user_func_array() are reported. A variable function, a method or a class named in a variable, and a function's name passed as a string or an array callable to any other function, such as array_map('trim', \$lines), are not.",
                'proof' => [self::conformance('test_nothing_is_called_by_a_computed_name'), self::conformance('test_nothing_is_called_by_a_computed_name_misses_names_no_function_takes')],
            ] + self::planned(),
            'nothing-is-imported-by-a-computed-path' => self::none(),
            'visibility-is-not-bypassed' => [
                'coverage' => 'partial',
                'checks' => ['disallowed-visibility-bypasses'],
                'gap' => "An (array) cast of an object, which gives its private properties under mangled keys, isn't reported, and neither is a private constant or a static property read through ReflectionClass's own methods, such as getConstant() and getStaticPropertyValue().",
                'proof' => [self::conformance('test_visibility_is_not_bypassed'), self::conformance('test_visibility_is_not_bypassed_misses_array_casts')],
            ] + self::planned(),

            // collection-values-come-from-named-operations
            'loops-compute-no-value-an-operation-names' => self::none(),
            'collection-operations-take-pure-functions' => self::partial(['disallowed-collection-walks'], "Only the built-in functions that exist to run a function for each element are reported. Nothing reads what a function passed to another function does, so one that calls a port, performs an impure operation or changes what it captured isn't reported, and PHP's shared collection operations, whose callers the clause is about, don't exist yet.", 'test_collection_operations_take_pure_functions', self::planned()),
            'folds-compute-no-value-a-narrower-operation-names' => self::none(),
            'collection-operations-come-from-one-place' => self::noneBecause("PHP's shared collection operations, which the banned built-ins would be replaced with, don't exist yet."),

            // constructors-only-store
            'constructors-only-store-their-arguments' => self::none(),
            'field-initialisers-are-constants' => [
                'coverage' => 'partial',
                'checks' => ['constant-property-defaults', 'parameter-default'],
                'gap' => 'From PHP 8.5 a static closure or a first-class callable is a constant expression, so a property whose default is one, which builds a Closure, compiles and passes.',
                'proof' => [self::conformance('test_field_initialisers_are_constants'), self::conformance('test_field_initialisers_are_constants_misses_closures')],
                'note' => "A promoted property's default is its parameter's, where PHP allows new, and parameter-default reports it.",
            ] + self::planned(),
            'core-types-have-factory-functions' => self::none(),
            'core-types-are-built-only-by-their-factory-functions' => self::noneBecause('PHP rejects a call to a private constructor from outside its class, and a write to a readonly property, but nothing makes a core type declare either, which core-types-have-factory-functions is for.'),
            'no-standard-library-or-third-party-code-builds-core-types' => self::partial(['disallowed-construction'], "An object mapper or a deserialiser from a library, such as Symfony Serializer or Valinor, isn't reported, which the dependency list is for.", 'test_no_standard_library_or_third_party_code_builds_core_types', self::planned()) + ['note' => 'The bans ask for more than the clause does: they report each of these calls whatever class it builds, a core type or not.'],
            'one-factory-function-calls-each-constructor' => self::none(),
            'factory-functions-take-signature-types' => self::none(),

            // declarations-are-typed
            'parameters-are-typed' => self::full(['phpstan-missing-types'], 'test_parameters_are_typed') + ['note' => self::LEVEL_CAN_BE_LOWERED],
            'return-types-are-declared-where-called-from-outside' => self::full(['phpstan-missing-types'], 'test_return_types_are_declared_where_called_from_outside') + ['note' => 'PHPStan asks more than the clause does: a private method declares its return type too. '.self::LEVEL_CAN_BE_LOWERED],
            'fields-are-typed' => self::full(['phpstan-missing-types'], 'test_fields_are_typed') + ['note' => self::LEVEL_CAN_BE_LOWERED],
            'collection-types-name-their-element-type' => self::full(['phpstan-missing-types'], 'test_collection_types_name_their_element_type') + ['note' => self::LEVEL_CAN_BE_LOWERED],

            // fields-are-set-once
            'fields-are-declared-read-only' => self::none(),
            'fields-are-assigned-only-at-construction' => self::none(),
            'field-collections-are-not-changed' => self::none(),

            // locals-are-not-reassigned
            'core-locals-are-not-reassigned-once-read' => self::none(),

            // no-boolean-parameters-or-fields
            'no-boolean-parameters' => self::none(),
            'no-boolean-fields' => self::none(),

            // no-default-parameter-values
            'no-parameter-default-values' => self::full(['parameter-default'], 'test_no_parameter_default_values'),
            'no-optional-parameters' => self::full(['parameter-default'], 'test_no_parameter_default_values') + ['note' => 'A PHP parameter is optional only through a default value, or by being variadic, which is not checked.'],

            // no-inheritance
            'classes-extend-only-allowed-bases' => self::full(['inheritance'], 'test_classes_extend_only_allowed_bases') + ['note' => "PHP's own exceptions may be extended, since a throwable class can't be written any other way."],
            'classes-are-final' => self::none(),
            'types-declare-every-method-they-have' => self::none(),

            // no-nullable-fields
            'core-type-fields-are-not-nullable' => self::none(),

            // only-calls-run-code
            'field-access-runs-no-code' => self::none(),
            'conversions-run-no-code' => self::none(),
            'only-the-constructor-is-a-magic-method' => self::none(),
            'iteration-runs-no-code' => self::none(),
            'no-hooks-reached-through-catch-all-parameters' => self::none(),
            'nothing-runs-when-a-value-is-collected' => self::none(),

            // open-interfaces-only-for-outside-variants
            'open-interfaces-are-not-matched-by-implementation' => self::none(),
            'no-port-is-sealed' => self::none(),

            // parameters-are-not-reassigned
            'parameters-are-not-reassigned' => self::none(),
            'no-reference-parameters' => self::none(),

            // shapes-follow-roles
            'every-type-has-one-shape' => self::none(),
            'only-adapter-and-entrypoint-code-references-plain-data-types' => self::none(),
            'the-core-takes-no-plain-data' => self::none(),

            // unknown-types-are-narrowed-at-the-edges
            'unknown-types-stay-in-adapter-and-entrypoint-code' => self::none(),
            'no-type-turns-checking-off' => self::full(['phpstan-mixed', 'phpstan-missing-types'], 'test_no_type_turns_checking_off') + ['note' => "PHP's type of this kind is mixed, declared or assumed where a type was left out. ".self::LEVEL_CAN_BE_LOWERED],
            'no-cast-asserts-an-unchecked-type' => [
                'coverage' => 'partial',
                'checks' => ['phpstan-mixed', 'disallowed-assert'],
                'gap' => "Only a cast of a mixed and assert() are reported. An inline @var that narrows a value, a phpstan-assert tag on a function that tests nothing, and a project's own stub file for third-party code are not.",
                'proof' => [self::conformance('test_no_cast_asserts_an_unchecked_type'), self::conformance('test_no_cast_asserts_an_unchecked_type_misses_annotations')],
            ] + self::planned(),

            // values-are-grouped-by-name
            'no-positional-groups' => self::none(),

            // values-are-not-changed-once-built
            'built-values-are-not-changed' => self::none(),

            // values-are-not-compared-by-identity
            'references-are-not-compared-by-identity' => self::none(),
            'collections-are-not-keyed-by-instance' => self::none(),

            // values-have-immutable-types
            'values-have-immutable-types' => self::none(),
            'collection-types-are-immutable' => self::none(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function errorHandling(): array
    {
        return [
            // core-does-not-throw
            'core-throws-nothing' => self::errors(self::planned()),
            'core-catches-expected-failures-it-calls' => [
                'coverage' => 'partial',
                'checks' => ['phpstan-checked-exceptions'],
                'gap' => 'Only a checked exception that escapes with no @throws tag is reported. A function that declares it in a @throws tag passes, and so does one that lets an unchecked exception escape, such as a RuntimeException a library throws for malformed input.',
                'proof' => [self::conformance('test_core_catches_expected_failures_it_calls'), self::conformance('test_core_catches_expected_failures_it_calls_misses_declared_exceptions')],
            ] + self::planned(),

            // driving-adapters-catch-each-invocation
            'driving-adapter-catches-each-invocation' => self::errors(self::planned()),
            'driving-adapter-code-catches-everything-only-around-invocations' => self::errors(self::planned()),
            'invocation-catch-all-reports-a-failure' => self::errors(self::declined('What a catch-all writes to its caller and its log is not decidable statically.')),
            'invocation-catch-all-keeps-internals-from-outside-callers' => self::errors(self::declined('What a catch-all writes to its caller is not decidable statically.')),

            // driving-adapters-report-returned-failures
            'driving-adapter-reports-returned-failures' => self::noneDeclined(self::REPORTS_NOT_DECIDABLE),
            'driving-adapter-reports-failures-a-decision-returns' => self::noneDeclined(self::REPORTS_NOT_DECIDABLE),

            // entrypoints-catch-what-escapes
            'entrypoint-catches-what-escapes' => self::errors(self::planned()),

            // error-types-are-shared-through-value-code
            'error-types-of-other-roles-are-not-matched' => self::partial(['deptrac-layers'], "Only a match on an exception class in a directory the matching code's role may not reference is reported, a first-party dependency's among them. A match in an entrypoint isn't checked, and neither is a driving adapter's match on an exception class under src/Orchestrators/, which its role may reference.", 'test_error_types_of_other_roles_are_not_matched', self::planned()),

            // orchestrators-return-failures
            'orchestrator-code-throws-nothing' => self::errors(self::planned()),
            'orchestrator-code-catches-nothing' => self::errors(self::planned()),

            // ports-return-expected-failures
            'port-methods-return-expected-failures' => [
                'coverage' => 'partial',
                'checks' => ['phpstan-checked-exceptions'],
                'gap' => "Only an implementation that throws a checked exception, or lets one escape, with no @throws tag is reported. One that declares it in a @throws tag passes: PHPStan compares the tag with the port's only when the port's method has one, and a port method tagged as throwing nothing would stop its implementations throwing for a failure no caller could handle too. One that throws a LogicException, a RuntimeException or a subclass passes, since those are unchecked. A port whose documentation names an exception isn't reported, and nothing reads whether a port method's result can carry each failure.",
                'proof' => [self::conformance('test_port_methods_return_expected_failures'), self::conformance('test_port_methods_return_expected_failures_misses_documented_and_unchecked_exceptions')],
            ] + self::planned(),
            'driven-adapter-code-converts-expected-failures' => [
                'coverage' => 'partial',
                'checks' => ['phpstan-checked-exceptions'],
                'gap' => "Only a checked exception that escapes a function with no @throws tag is reported. A driver's exception that extends RuntimeException, as PDOException does for a unique-constraint violation, is unchecked and escapes unreported, and so does a RuntimeException the adapter throws itself for an expected failure. A method that declares a checked exception in a @throws tag passes. A catch of \\Throwable or \\Exception, which catches more than expected failures, isn't reported.",
                'proof' => [self::conformance('test_driven_adapter_code_converts_expected_failures'), self::conformance('test_driven_adapter_code_converts_expected_failures_misses_declared_and_unchecked_exceptions')],
            ] + self::planned(),

            // results-are-not-dropped
            'returned-failures-are-used' => [
                'coverage' => 'partial',
                'checks' => ['no-discard'],
                'gap' => 'Only a function or method marked #[\\NoDiscard] is checked, and a result assigned to a variable that is never read passes.',
                'proof' => [self::conformance('test_returned_failures_are_used'), self::conformance('test_returned_failures_are_used_misses_unmarked_functions')],
            ] + self::planned(),
            'failure-branches-act' => self::none(),
            'explicit-discards-give-a-reason' => self::none(),

            // sentinels-report-only-absence
            'sentinels-report-only-absence' => self::errors(self::declined("What a returned null means, that the one thing asked for doesn't exist or that something else went wrong, isn't in the code.")),
            'sentinels-are-null-or-a-found-flag' => self::errors(self::planned()),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function persistence(): array
    {
        return [
            // each-table-has-an-id-type
            'each-project-table-has-an-id-type' => self::none(),
            'id-types-accept-only-uuidv7s' => self::none(),
            'ids-are-held-as-their-id-types' => self::none(),
            'only-the-shared-id-unit-of-code-uses-third-party-uuid-code' => self::none(),

            // enums-are-stored-by-value
            'enums-are-stored-as-their-values' => self::none(),
            'enum-columns-admit-only-their-enums-values' => self::none(),

            // every-row-has-a-uuidv7-id
            'every-project-table-has-a-uuid-id-primary-key' => self::none(),
            'id-columns-reject-other-uuid-versions' => self::none(),
            'ids-say-nothing-about-when-rows-were-made' => self::none(),

            // rows-referred-to-from-outside-have-a-public-id
            'public-id-columns-are-unique-uuidv4s' => self::none(),
            'each-public-id-has-a-type' => self::none(),
            'public-id-types-accept-only-uuidv4s' => self::none(),

            // sealed-unions-are-stored-by-variant
            'nullable-columns-hold-only-variant-data' => self::none(),
            'variant-data-columns-are-constrained-to-their-variants' => self::none(),

            // sql-is-built-from-query-data
            'sql-text-comes-only-from-the-query-compiler' => self::none(),
            'queries-are-query-data' => self::none(),
            'queries-are-built-in-driven-adapter-code' => self::none(),

            // the-database-decides-nothing
            'every-stored-value-comes-from-the-query' => self::none(),
            'queries-perform-no-impure-operation' => self::none(),
            'the-schema-supplies-no-values' => self::none(),
            'no-value-depends-on-session-settings' => self::none(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function formattingProjectAndTesting(): array
    {
        return [
            // files-match-the-formatter
            'check-fails-on-unformatted-files' => self::convention('README.md, Use: the check task runs vendor/bin/pint --test', self::planned()),
            'formatter-settings-come-from-the-baseline' => self::convention("README.md, Use: pint.json extends the baseline's config/pint.json", self::planned()),

            // checks-run-at-full-strength
            'type-checker-runs-at-its-strictest' => self::convention('README.md, Use: a project sets no lower level', self::planned()) + ['note' => 'config/phpstan.neon sets level 10, and nothing reports a project whose own phpstan.neon sets a lower one.'],
            'baseline-settings-are-not-weakened' => self::convention("README.md, Use: a project turns off nothing the baseline's configs turn on", self::planned()),
            'checks-are-not-suppressed' => self::convention('README.md, Use: a project ignores no error and excludes none of its code', self::planned()),
            'check-task-runs-every-baseline-check' => self::convention('README.md, Use: what the check task runs', self::planned()),

            // dependencies-are-vetted
            'every-third-party-dependency-is-listed' => self::noneBecause('The baseline has no dependency list yet.'),

            // one-listed-dependency-per-capability
            'at-most-one-listed-dependency-per-capability' => self::noneBecause('The baseline has no dependency list yet.'),

            // tool-caches-are-ignored
            'caches-live-under-the-cache-directory' => self::convention('README.md, Use: the tools keep their caches under cache/', self::planned()),
            'gitignore-ignores-the-cache-directory' => [
                'coverage' => 'full',
                'checks' => ['verify-gitignore'],
                'proof' => [
                    'tests/Core/ProjectProblemsTest.php: test_a_project_without_a_gitignore_is_asked_for_one',
                    'tests/Core/ProjectProblemsTest.php: test_a_gitignore_without_the_cache_line_is_reported',
                ],
            ],

            // contract-tests-run-against-fakes-and-adapters
            'every-port-has-one-set-of-contract-tests' => self::none(),
            'fake-and-adapter-tests-run-the-contract-tests' => self::none(),

            // core-tests-kill-mutants
            'check-task-runs-mutation-testing-on-the-core' => self::none(),

            // core-tests-take-only-values
            'core-tests-reference-only-the-core' => self::noneBecause('deptrac reads src/ and bin/, not tests/, and has no layer for test code.'),

            // each-port-has-one-fake
            'every-port-has-one-fake' => self::none(),
            'only-driven-adapters-and-fakes-stand-in-for-ports' => self::none(),
            'fake-code-references-only-ports-values-and-fakes' => self::partial(['deptrac-layers'], self::NO_DECISIONS." A fake that references decision code isn't reported, nor one that uses third-party code, since deptrac leaves out classes in no layer.", 'test_fake_code_references_only_ports_values_and_fakes', self::planned()),
            'fake-code-performs-only-held-state' => self::none(),
            'only-test-code-references-fake-code' => self::partial(['deptrac-layers'], "An entrypoint that builds a fake isn't reported: a script in bin/ or public/ is in no layer. Test code a project ships, under src/Testing/, counts as test code by its directory.", 'test_only_test_code_references_fake_code', self::planned()),

            // orchestrator-tests-call-once
            'orchestrator-test-case-calls-one-use-case-once' => self::none(),

            // ports-are-doubled-by-hand-written-fakes
            'no-mocking-libraries' => self::partial(['disallowed-mocking'], "Only PHPUnit's test doubles, Mockery and Prophecy are reported. Another mocking library, such as Phake, isn't, which the dependency list is for.", 'test_no_mocking_libraries', self::planned()),
            'no-hand-written-scripted-doubles' => self::none(),
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
     * A check of the baseline's own: a PHPStan rule in src/Adapters/Driving/PhpStan/, or verify.
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
     * @param  array<string, string>  $intent
     * @return array<string, mixed>
     */
    private static function partial(array $checks, string $gap, string $test, array $intent): array
    {
        return ['coverage' => 'partial', 'checks' => $checks, 'gap' => $gap, 'proof' => [self::conformance($test)]] + $intent;
    }

    /**
     * @param  array<string, string>  $intent
     * @return array<string, string>
     */
    private static function convention(string $where, array $intent): array
    {
        return ['coverage' => 'convention-only', 'where' => $where] + $intent;
    }

    /**
     * A clause the README's Errors section states, which nothing checks.
     *
     * @param  array<string, string>  $intent
     * @return array<string, string>
     */
    private static function errors(array $intent): array
    {
        return self::convention('README.md, Errors', $intent);
    }

    /**
     * @return array<string, string>
     */
    private static function none(): array
    {
        return ['coverage' => 'none'] + self::planned();
    }

    /**
     * @return array<string, string>
     */
    private static function noneBecause(string $note): array
    {
        return self::none() + ['note' => $note];
    }

    /**
     * Nothing checks it or states it, and no check is to be added, for the reason given.
     *
     * @return array<string, string>
     */
    private static function noneDeclined(string $reason): array
    {
        return ['coverage' => 'none'] + self::declined($reason);
    }

    /**
     * A check is to be added, or extended to close the gap.
     *
     * @return array<string, string>
     */
    private static function planned(): array
    {
        return ['intent' => 'planned'];
    }

    /**
     * No check is to be added, for the reason given.
     *
     * @return array<string, string>
     */
    private static function declined(string $reason): array
    {
        return ['intent' => 'declined', 'reason' => $reason];
    }

    private static function conformance(string $test): string
    {
        return 'tests/ConformanceTest.php: '.$test;
    }
}
// COUPLED:coverage-listing-schema:END
