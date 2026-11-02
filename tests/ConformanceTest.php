<?php

declare(strict_types=1);

namespace IanFlanagan\PhpBaseline\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Shows the shipped deptrac and PHPStan configs catching each violation of the rulebook's clauses
 * that the listing claims. tests/fixtures/conformance has a file for each violation, and each test
 * is named for its clause.
 */
final class ConformanceTest extends TestCase
{
    private const string FIXTURE = __DIR__.'/fixtures/conformance';

    /** @var array<string, list<string>>|null deptrac's violations, by file, as "<layer> -> <layer>" */
    private static ?array $layerViolations = null;

    /** @var array<string, list<string>>|null PHPStan's errors, by file, as their identifiers */
    private static ?array $phpstanErrors = null;

    public function test_every_unit_has_one_role(): void
    {
        $this->assertSame([], self::layerViolations('src/Core/Pure.php'));
        $this->assertSame([], self::layerViolations('src/Ports/Clock.php'));
        $this->assertSame([], self::layerViolations('src/Adapters/Driven/SystemClock.php'));
        $this->assertSame([], self::layerViolations('src/Adapters/Driving/Server.php'));
        $this->assertSame([], self::layerViolations('src/Orchestrators/Greeter.php'));
        $this->assertSame([], self::layerViolations('bin/app.php'));
        $this->assertSame(['disallowed.function'], self::phpstanErrors('src/Adapters/Unplaced.php'));
        $this->assertSame(['Unplaced -> Core'], self::layerViolations('src/App/Stray.php'));
        $this->assertSame(['Orchestrator -> Unplaced'], self::layerViolations('src/Orchestrators/UsesStray.php'));
    }

    public function test_a_dependency_of_mine_has_the_roles_its_namespace_gives(): void
    {
        $this->assertSame([], self::layerViolations('src/Core/UsesLibraryCore.php'));
        $this->assertSame([], self::layerViolations('src/Adapters/Driven/ImplementsLibraryPort.php'));
        $this->assertSame([], self::layerViolations('src/Orchestrators/UsesLibraryOrchestrator.php'));
        $this->assertSame(['Core -> Orchestrator'], self::layerViolations('src/Core/UsesLibraryOrchestrator.php'));
        $this->assertSame(['Orchestrator -> DrivenAdapter'], self::layerViolations('src/Orchestrators/UsesLibraryAdapter.php'));
    }

    public function test_core_imports_only_core(): void
    {
        $this->assertSame(['Core -> Orchestrator'], self::layerViolations('src/Core/ImportsOrchestrator.php'));
        $this->assertSame(['Core -> DrivenAdapter'], self::layerViolations('src/Core/ImportsAdapter.php'));
        $this->assertSame(['Core -> DrivingAdapter'], self::layerViolations('src/Core/ImportsDriving.php'));
        $this->assertSame(['Core -> Port'], self::layerViolations('src/Core/ImportsPort.php'));
    }

    public function test_ports_are_shared_by_orchestrators_and_driven_adapters_and_import_only_the_core(): void
    {
        $this->assertSame([], self::layerViolations('src/Orchestrators/UsesPort.php'));
        $this->assertSame([], self::layerViolations('src/Adapters/Driven/SystemClock.php'));
        $this->assertSame(['Port -> Orchestrator'], self::layerViolations('src/Ports/ImportsOrchestrator.php'));
        $this->assertSame(['Port -> DrivenAdapter'], self::layerViolations('src/Ports/ImportsAdapter.php'));
    }

    public function test_orchestrator_imports_no_adapter(): void
    {
        $this->assertSame(['Orchestrator -> DrivenAdapter'], self::layerViolations('src/Orchestrators/ImportsAdapter.php'));
        $this->assertSame(['Orchestrator -> DrivingAdapter'], self::layerViolations('src/Orchestrators/ImportsDriving.php'));
    }

    public function test_driving_adapter_imports_only_core_orchestrators_and_driving_adapters(): void
    {
        $this->assertSame(['DrivingAdapter -> DrivenAdapter'], self::layerViolations('src/Adapters/Driving/ImportsDriven.php'));
        $this->assertSame(['DrivingAdapter -> Port'], self::layerViolations('src/Adapters/Driving/ImportsPort.php'));
        $this->assertSame([], self::layerViolations('src/Adapters/Driving/Server.php'));
    }

    public function test_driven_adapter_imports_only_core_ports_and_driven_adapters(): void
    {
        $this->assertSame(['DrivenAdapter -> Orchestrator'], self::layerViolations('src/Adapters/Driven/ImportsOrchestrator.php'));
        $this->assertSame(['DrivenAdapter -> DrivingAdapter'], self::layerViolations('src/Adapters/Driven/ImportsDriving.php'));
    }

    public function test_core_performs_no_effects(): void
    {
        $this->assertEffectsAreReported('src/Core');
        $this->assertSame(['phpBaseline.staticState'], self::phpstanErrors('src/Core/StaticProperty.php'));
        $this->assertSame([], self::phpstanErrors('src/Core/Pure.php'));
        $this->assertSame([], self::phpstanErrors('src/Adapters/Driven/StaticCache.php'));
    }

    public function test_orchestrator_performs_no_effects(): void
    {
        $this->assertEffectsAreReported('src/Orchestrators');
        $this->assertSame(['phpBaseline.staticState'], self::phpstanErrors('src/Orchestrators/StaticVariable.php'));
        $this->assertSame([], self::phpstanErrors('src/Orchestrators/Greeter.php'));
    }

    public function test_only_entrypoints_read_configuration(): void
    {
        $this->assertSame(['disallowed.function', 'disallowed.variable'], self::phpstanErrors('src/Core/Configuration.php'));
        $this->assertSame(['disallowed.function', 'disallowed.variable'], self::phpstanErrors('src/Orchestrators/Configuration.php'));
        $this->assertSame(['disallowed.function'], self::phpstanErrors('src/Adapters/Driven/ReadsConfiguration.php'));
        $this->assertSame([], self::phpstanErrors('bin/app.php'));
        $this->assertSame([], self::phpstanErrors('src/Adapters/Driven/SystemClock.php'));
    }

    public function test_adapter_size_is_limited(): void
    {
        $this->assertSame(['phpBaseline.functionLength'], self::phpstanErrors('src/Adapters/Driven/Long.php'));
    }

    public function test_entrypoint_size_is_limited(): void
    {
        $this->assertSame(['phpBaseline.scriptLength'], self::phpstanErrors('bin/long.php'));
    }

    public function test_wired_without_a_container(): void
    {
        $this->assertSame(['disallowed.namespace', 'disallowed.interface'], self::phpstanErrors('src/Orchestrators/Container.php'));
        $this->assertContains('disallowed.class', self::phpstanErrors('bin/container.php'));
    }

    public function test_no_import_cycles(): void
    {
        $this->assertSame(['phpBaseline.dependencyCycle'], self::phpstanErrors('src/Cycle/A.php'));
        $this->assertSame([], self::phpstanErrors('src/Cycle/B.php'));
        $this->assertSame([], self::phpstanErrors('src/Design/Families.php'));
    }

    public function test_closed_families_are_sealed(): void
    {
        $this->assertSame(['class.disallowedSubtype'], self::phpstanErrors('src/Design/Unlisted.php'));
    }

    public function test_matches_on_closed_families_are_exhaustive(): void
    {
        $this->assertSame(['match.unhandled', 'match.unhandled'], self::phpstanErrors('src/Design/MissingCase.php'));
        $this->assertSame(['phpBaseline.switchOnClosedFamily'], self::phpstanErrors('src/Design/SwitchOnEnum.php'));
        $this->assertSame([], self::phpstanErrors('src/Design/Exhaustive.php'));
    }

    public function test_matches_on_closed_families_have_no_default_arm(): void
    {
        $this->assertSame(['phpBaseline.matchDefaultArm'], self::phpstanErrors('src/Design/EnumDefault.php'));
        $this->assertContains('phpBaseline.matchDefaultArm', self::phpstanErrors('src/Design/SealedDefault.php'));
    }

    public function test_classes_extend_only_allowed_bases(): void
    {
        $this->assertSame(['phpBaseline.inheritance'], self::phpstanErrors('src/Design/ExtendsProjectClass.php'));
        $this->assertSame(['phpBaseline.inheritance'], self::phpstanErrors('src/Design/ExtendsAbstractClass.php'));
        $this->assertSame(['phpBaseline.inheritance'], self::phpstanErrors('src/Design/ExtendsLibraryClass.php'));
        $this->assertSame([], self::phpstanErrors('src/Design/ExtendsAllowedClass.php'));
    }

    public function test_no_parameter_default_values(): void
    {
        $this->assertSame(array_fill(0, 3, 'phpBaseline.parameterDefault'), self::phpstanErrors('src/Design/Defaults.php'));
        $this->assertSame([], self::phpstanErrors('src/Design/MatchesSignature.php'));
    }

    public function test_returned_failures_are_used(): void
    {
        $this->assertSame(
            ['function.resultDiscarded', 'function.resultUnused', 'method.resultDiscarded', 'method.resultUnused'],
            self::phpstanErrors('src/Design/DropsResult.php'),
        );
    }

    /**
     * Pins the gap the listing records, so closing it fails this test until the listing changes too.
     */
    public function test_returned_failures_are_used_misses_unmarked_functions(): void
    {
        $this->assertSame([], self::phpstanErrors('src/Design/DropsUnmarkedSentinel.php'));
    }

    private function assertEffectsAreReported(string $dir): void
    {
        $expected = [
            'Clock' => ['disallowed.function', 'disallowed.new'],
            'Randomness' => ['disallowed.function'],
            'Filesystem' => ['disallowed.function'],
            'Network' => ['disallowed.class', 'disallowed.class', 'disallowed.function'],
            'Process' => ['disallowed.function'],
            'Console' => ['disallowed.echo'],
            'Logging' => ['disallowed.function'],
            'Configuration' => ['disallowed.function', 'disallowed.variable'],
            'GlobalState' => ['disallowed.global'],
        ];
        foreach ($expected as $effect => $identifiers) {
            $this->assertSame($identifiers, self::phpstanErrors("{$dir}/{$effect}.php"), $effect);
        }
    }

    /**
     * @return list<string>
     */
    private static function layerViolations(string $file): array
    {
        self::$layerViolations ??= self::runDeptrac();

        return self::$layerViolations[$file] ?? [];
    }

    /**
     * @return list<string>
     */
    private static function phpstanErrors(string $file): array
    {
        self::$phpstanErrors ??= self::runPhpstan();

        return self::$phpstanErrors[$file] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    private static function runDeptrac(): array
    {
        $report = tempnam(sys_get_temp_dir(), 'deptrac');
        self::runTool(['vendor/bin/deptrac', 'analyse', '--no-progress', '--formatter=json', '--output='.$report]);
        $violations = [];
        foreach (self::messagesByFile((string) file_get_contents($report)) as $file => $messages) {
            foreach ($messages as $message) {
                if (preg_match('/\((\w+) on (\w+)\)$/', $message['message'], $layers) !== 1) {
                    self::fail("deptrac reported {$message['message']}, which names no layers");
                }
                $violations[self::relative($file)][] = "{$layers[1]} -> {$layers[2]}";
            }
        }

        return $violations;
    }

    /**
     * @return array<string, list<string>>
     */
    private static function runPhpstan(): array
    {
        $errors = [];
        foreach (self::messagesByFile(self::runTool(['vendor/bin/phpstan', 'analyse', '--no-progress', '--error-format=json'])) as $file => $messages) {
            foreach ($messages as $message) {
                $errors[self::relative($file)][] = $message['identifier'] ?? '';
            }
        }

        return $errors;
    }

    /**
     * Runs a tool from this package's vendor/bin in the fixture, and returns its standard output.
     *
     * @param  list<string>  $command
     */
    private static function runTool(array $command): string
    {
        $command[0] = dirname(__DIR__).'/'.$command[0];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, self::FIXTURE);
        self::assertIsResource($process);
        $output = (string) stream_get_contents($pipes[1]);
        $errors = (string) stream_get_contents($pipes[2]);
        $status = proc_close($process);
        self::assertContains($status, [0, 1], "{$command[0]} failed:\n{$errors}");

        return $output;
    }

    /**
     * Reads the messages from a report that deptrac or PHPStan wrote as JSON, which both shape
     * the same way.
     *
     * @return array<string, list<array{message: string, identifier?: string}>>
     */
    private static function messagesByFile(string $json): array
    {
        /** @var array{files: array<string, array{messages: list<array{message: string, identifier?: string}>}>} $report */
        $report = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return array_map(static fn (array $file): array => $file['messages'], $report['files']);
    }

    private static function relative(string $file): string
    {
        $prefix = realpath(self::FIXTURE).'/';

        return str_starts_with($file, $prefix) ? substr($file, strlen($prefix)) : $file;
    }
}
