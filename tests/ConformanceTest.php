<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Tests;

use JsonException;
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

    /** @var array<string, list<array{identifier: string, message: string}>>|null PHPStan's errors, by file */
    private static ?array $phpstanErrors = null;

    public function test_every_declaration_has_one_role(): void
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
        $this->assertSame([], self::layerViolations('src/Fakes/FakeClock.php'));
        $this->assertSame([], self::layerViolations('src/Testing/ClockAssertion.php'));
        $this->assertSame(['Testing -> DrivenAdapter'], self::layerViolations('src/Testing/TestingImportsAdapter.php'));
        $this->assertSame(['Core -> Testing'], self::layerViolations('src/Core/ImportsTesting.php'));
    }

    public function test_a_unit_of_code_holds_one_role(): void
    {
        $this->assertSame(['DrivingAdapter -> Port'], self::layerViolations('src/Adapters/Driving/ControllerAndOrchestrator.php'));
    }

    public function test_dependency_declarations_keep_their_role(): void
    {
        $this->assertSame([], self::layerViolations('src/Core/UsesLibraryCore.php'));
        $this->assertSame([], self::layerViolations('src/Adapters/Driven/ImplementsLibraryPort.php'));
        $this->assertSame([], self::layerViolations('src/Orchestrators/UsesLibraryOrchestrator.php'));
        $this->assertSame(['Core -> Orchestrator'], self::layerViolations('src/Core/UsesLibraryOrchestrator.php'));
        $this->assertSame(['Orchestrator -> DrivenAdapter'], self::layerViolations('src/Orchestrators/UsesLibraryAdapter.php'));
    }

    public function test_core_references_only_core(): void
    {
        $this->assertSame(['Core -> Orchestrator'], self::layerViolations('src/Core/ImportsOrchestrator.php'));
        $this->assertSame(['Core -> Orchestrator'], self::layerViolations('src/Core/NamesOrchestratorInFull.php'));
        $this->assertSame(['Core -> DrivenAdapter'], self::layerViolations('src/Core/ImportsAdapter.php'));
        $this->assertSame(['Core -> DrivingAdapter'], self::layerViolations('src/Core/ImportsDriving.php'));
        $this->assertSame(['Core -> Port'], self::layerViolations('src/Core/ImportsPort.php'));
    }

    public function test_value_code_references_only_value_code(): void
    {
        $this->assertSame(['Core -> Orchestrator'], self::layerViolations('src/Core/ImportsOrchestrator.php'));
        $this->assertSame([], self::layerViolations('src/Core/UsesLibraryCore.php'));
    }

    public function test_ports_reference_only_value_code(): void
    {
        $this->assertSame(['Port -> Orchestrator'], self::layerViolations('src/Ports/ImportsOrchestrator.php'));
        $this->assertSame([], self::layerViolations('src/Ports/Clock.php'));
    }

    public function test_port_is_declared_by_its_caller(): void
    {
        $this->assertSame([], self::layerViolations('src/Orchestrators/UsesPort.php'));
        $this->assertSame([], self::layerViolations('src/Adapters/Driven/SystemClock.php'));
        $this->assertSame(['Port -> Orchestrator'], self::layerViolations('src/Ports/ImportsOrchestrator.php'));
        $this->assertSame(['Port -> DrivenAdapter'], self::layerViolations('src/Ports/ImportsAdapter.php'));
    }

    /**
     * The interface declared in an orchestrator's file is reported only where a driven adapter
     * implements it, which is the gap the listing records.
     */
    public function test_port_units_of_code_follow_interface_implementation(): void
    {
        $this->assertSame(['DrivenAdapter -> Orchestrator'], self::layerViolations('src/Adapters/Driven/ImplementsOrchestratorsInterface.php'));
        $this->assertSame([], self::layerViolations('src/Orchestrators/DeclaresItsPort.php'));
        $this->assertSame([], self::layerViolations('src/Adapters/Driven/SystemClock.php'));
    }

    public function test_orchestrator_code_references_no_entrypoint_or_adapter_code(): void
    {
        $this->assertSame(['Orchestrator -> DrivenAdapter'], self::layerViolations('src/Orchestrators/ImportsAdapter.php'));
        $this->assertSame(['Orchestrator -> DrivingAdapter'], self::layerViolations('src/Orchestrators/ImportsDriving.php'));
    }

    public function test_driving_adapter_code_references_only_orchestrators_and_the_core(): void
    {
        $this->assertSame(['DrivingAdapter -> DrivenAdapter'], self::layerViolations('src/Adapters/Driving/ImportsDriven.php'));
        $this->assertSame(['DrivingAdapter -> Port'], self::layerViolations('src/Adapters/Driving/ImportsPort.php'));
        $this->assertSame([], self::layerViolations('src/Adapters/Driving/Server.php'));
    }

    public function test_driving_adapter_code_calls_no_driven_adapter(): void
    {
        $this->assertSame(['DrivingAdapter -> Port'], self::layerViolations('src/Adapters/Driving/ImportsPort.php'));
        $this->assertSame(['DrivingAdapter -> DrivenAdapter'], self::layerViolations('src/Adapters/Driving/ReadsLibraryClock.php'));
        $this->assertSame([], self::layerViolations('src/Adapters/Driving/Server.php'));
    }

    public function test_driven_adapter_code_references_only_ports_and_values(): void
    {
        $this->assertSame(['DrivenAdapter -> Orchestrator'], self::layerViolations('src/Adapters/Driven/ImportsOrchestrator.php'));
        $this->assertSame(['DrivenAdapter -> DrivingAdapter'], self::layerViolations('src/Adapters/Driven/ImportsDriving.php'));
        $this->assertSame([], self::layerViolations('src/Adapters/Driven/SystemClock.php'));
    }

    public function test_driven_adapter_code_calls_no_orchestrator(): void
    {
        $this->assertSame(['DrivenAdapter -> Orchestrator'], self::layerViolations('src/Adapters/Driven/ImportsOrchestrator.php'));
    }

    public function test_error_types_of_other_roles_are_not_matched(): void
    {
        $this->assertSame(['DrivingAdapter -> DrivenAdapter'], self::layerViolations('src/Adapters/Driving/CatchesDrivenException.php'));
        $this->assertSame(['DrivingAdapter -> DrivenAdapter'], self::layerViolations('src/Adapters/Driving/CatchesLibraryDrivenException.php'));
        $this->assertSame([], self::layerViolations('src/Adapters/Driving/CatchesStandardException.php'));
    }

    public function test_port_methods_return_expected_failures(): void
    {
        $this->assertSame(['missingType.checkedException'], self::phpstanErrors('src/Adapters/Driven/ThrowsWhenTheRowIsMissing.php'));
        $this->assertSame([], self::phpstanErrors('src/Ports/Orders.php'));
    }

    /**
     * Pins a gap the listing records, so closing it fails this test until the listing changes too.
     */
    public function test_port_methods_return_expected_failures_misses_documented_and_unchecked_exceptions(): void
    {
        $this->assertSame([], self::phpstanErrors('src/Ports/DocumentsAnException.php'));
        $this->assertSame([], self::phpstanErrors('src/Adapters/Driven/DeclaresWhatItThrows.php'));
        $this->assertSame([], self::phpstanErrors('src/Adapters/Driven/ThrowsAnUncheckedException.php'));
    }

    public function test_driven_adapter_code_converts_expected_failures(): void
    {
        $this->assertSame(['missingType.checkedException'], self::phpstanErrors('src/Adapters/Driven/LetsTheParserThrow.php'));
        $this->assertSame(['missingType.checkedException'], self::phpstanErrors('src/Adapters/Driven/ThrowsWhenTheRowIsMissing.php'));
        $this->assertSame([], self::phpstanErrors('src/Adapters/Driven/ConvertsTheFailure.php'));
    }

    /**
     * Pins a gap the listing records, so closing it fails this test until the listing changes too.
     */
    public function test_driven_adapter_code_converts_expected_failures_misses_declared_and_unchecked_exceptions(): void
    {
        $this->assertSame([], self::phpstanErrors('src/Adapters/Driven/DeclaresWhatItThrows.php'));
        $this->assertSame([], self::phpstanErrors('src/Adapters/Driven/ThrowsAnUncheckedException.php'));
    }

    public function test_core_catches_expected_failures_it_calls(): void
    {
        $this->assertSame(['missingType.checkedException'], self::phpstanErrors('src/Core/LetsAConversionThrow.php'));
        $this->assertSame([], self::phpstanErrors('src/Core/CatchesAConversionsFailure.php'));
    }

    /**
     * Pins a gap the listing records, so closing it fails this test until the listing changes too.
     */
    public function test_core_catches_expected_failures_it_calls_misses_declared_exceptions(): void
    {
        $this->assertSame([], self::phpstanErrors('src/Core/DeclaresAConversionsException.php'));
    }

    public function test_core_does_nothing_impure(): void
    {
        $this->assertEffectsAreReported('src/Core');
        $this->assertSame(['disallowed.function'], self::phpstanErrors('src/Core/LocalTime.php'));
        $this->assertSame(['disallowed.function'], self::phpstanErrors('src/Core/Weekday.php'));
        $this->assertSame(['phpBaseline.staticState'], self::phpstanErrors('src/Core/StaticProperty.php'));
        $this->assertSame([], self::phpstanErrors('src/Core/Pure.php'));
    }

    /**
     * Pins a gap the listing records, so closing it fails this test until the listing changes too.
     */
    public function test_core_does_nothing_impure_misses_times_in_the_local_zone(): void
    {
        $this->assertSame([], self::phpstanErrors('src/Core/ParsesInLocalZone.php'));
    }

    public function test_core_calls_reach_nothing_impure(): void
    {
        $this->assertSame(['phpBaseline.coreCallReachesImpure'], self::phpstanErrors('src/Core/StampsWithALibrary.php'));
        $this->assertStringContainsString(
            'Acme\\Toolkit\\stamp() -> Acme\\Toolkit\\Clock::seconds() -> time()',
            implode("\n", self::phpstanMessages('src/Core/StampsWithALibrary.php')),
        );
        $this->assertSame(['phpBaseline.coreCallReachesImpure'], self::phpstanErrors('src/Core/MemoisesWithALibrary.php'));
        $this->assertSame(['phpBaseline.coreCallReachesImpure'], self::phpstanErrors('src/Core/CountsWithALibrary.php'));
        $this->assertSame(['phpBaseline.coreCallUnresolved'], self::phpstanErrors('src/Core/ShoutsWithALibrary.php'));
        $this->assertSame(['phpBaseline.coreCallUnresolved'], self::phpstanErrors('src/Core/RendersWithALibrary.php'));
        $this->assertSame([], self::phpstanErrors('src/Core/UsesAPureLibrary.php'));
        $this->assertSame([], self::phpstanErrors('src/Core/AppliesWithALibrary.php'));
    }

    public function test_core_loading_does_nothing_impure(): void
    {
        $this->assertSame(['disallowed.function'], self::phpstanErrors('src/Core/LoadTime.php'));
        $this->assertSame(['phpBaseline.coreLoadingDoesImpure'], self::phpstanErrors('src/Core/LoadsALibrary.php'));
        $this->assertStringContainsString(
            'loads vendor/acme/settings/bootstrap.php -> getenv()',
            implode("\n", self::phpstanMessages('src/Core/LoadsALibrary.php')),
        );
    }

    public function test_orchestrator_code_performs_no_effects(): void
    {
        $this->assertEffectsAreReported('src/Orchestrators');
        $this->assertSame(['phpBaseline.staticState'], self::phpstanErrors('src/Orchestrators/StaticVariable.php'));
        $this->assertSame([], self::phpstanErrors('src/Orchestrators/Greeter.php'));
    }

    public function test_adapters_and_entrypoints_perform_only_their_roles_operations(): void
    {
        $this->assertSame(['disallowed.function'], self::phpstanErrors('src/Adapters/Driving/FetchesAPage.php'));
        $this->assertSame(['disallowed.class'], self::phpstanErrors('src/Adapters/Driving/LooksUpASession.php'));
        $this->assertSame(['disallowed.global'], self::phpstanErrors('src/Adapters/Driving/CountsRequests.php'));
        $this->assertSame(['disallowed.function'], self::phpstanErrors('src/Adapters/Driven/ServesWebhooks.php'));
        $this->assertSame(['disallowed.function'], self::phpstanErrors('src/Adapters/Driven/ReadsConfiguration.php'));
        $this->assertSame(['phpBaseline.staticState'], self::phpstanErrors('src/Adapters/Driven/StaticCache.php'));
        $this->assertSame(['disallowed.function'], self::phpstanErrors('bin/checks-health.php'));
        $this->assertSame(['disallowed.function'], self::phpstanErrors('bin/listens.php'));
        $this->assertSame([], self::phpstanErrors('src/Adapters/Driving/AnswersRequests.php'));
        $this->assertSame([], self::phpstanErrors('src/Adapters/Driven/CallsOut.php'));
        $this->assertSame([], self::phpstanErrors('src/Adapters/Driven/SystemClock.php'));
        $this->assertSame([], self::phpstanErrors('bin/app.php'));
    }

    /**
     * Pins a gap the listing records, so closing it fails this test until the listing changes too.
     */
    public function test_adapters_and_entrypoints_perform_only_their_roles_operations_misses_the_clock_and_urls_opened_as_files(): void
    {
        $this->assertSame([], self::phpstanErrors('src/Adapters/Driving/ReadsTheClock.php'));
        $this->assertSame([], self::phpstanErrors('src/Adapters/Driving/FetchesAPageAsAFile.php'));
    }

    public function test_nothing_sleeps(): void
    {
        $this->assertSame(['disallowed.function'], self::phpstanErrors('src/Core/Sleeps.php'));
        $this->assertSame(['disallowed.function'], self::phpstanErrors('src/Adapters/Driven/SleepsBetweenAttempts.php'));
        $this->assertSame(array_fill(0, 3, 'disallowed.function'), self::phpstanErrors('src/Adapters/Driving/SleepsBeforePolling.php'));
        $this->assertSame(['disallowed.function'], self::phpstanErrors('bin/sleeps.php'));
        $this->assertSame(['disallowed.function'], self::phpstanErrors('tests/SleepsInATest.php'));
    }

    public function test_only_entrypoint_code_reads_configuration(): void
    {
        $this->assertSame(['disallowed.function', 'disallowed.variable'], self::phpstanErrors('src/Core/Configuration.php'));
        $this->assertSame(['disallowed.function', 'disallowed.variable'], self::phpstanErrors('src/Orchestrators/Configuration.php'));
        $this->assertSame(['disallowed.function'], self::phpstanErrors('src/Adapters/Driven/ReadsConfiguration.php'));
        $this->assertSame([], self::phpstanErrors('bin/app.php'));
        $this->assertSame([], self::phpstanErrors('src/Adapters/Driven/SystemClock.php'));
    }

    public function test_adapter_code_function_size_is_limited(): void
    {
        $this->assertSame(['phpBaseline.functionLength'], self::phpstanErrors('src/Adapters/Driven/Long.php'));
    }

    public function test_entrypoint_code_function_size_is_limited(): void
    {
        $this->assertSame(['phpBaseline.scriptLength'], self::phpstanErrors('bin/long.php'));
    }

    public function test_entrypoint_code_wires_without_a_container(): void
    {
        $this->assertSame(['disallowed.namespace', 'disallowed.interface'], self::phpstanErrors('src/Orchestrators/Container.php'));
        $this->assertContains('disallowed.class', self::phpstanErrors('bin/container.php'));
    }

    public function test_no_reference_cycles(): void
    {
        $this->assertSame(['phpBaseline.dependencyCycle'], self::phpstanErrors('src/Cycle/A.php'));
        $this->assertSame([], self::phpstanErrors('src/Cycle/B.php'));
        $this->assertSame(['phpBaseline.dependencyCycle'], self::phpstanErrors('src/Cycle/VariantCycle.php'));
        $this->assertSame([], self::phpstanErrors('src/Cycle/NamesItsVariant.php'));
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
        $this->assertSame(
            ['phpBaseline.matchDefaultArm', 'phpBaseline.matchDefaultArm'],
            array_values(array_intersect(self::phpstanErrors('src/Design/ExhaustiveDefault.php'), ['phpBaseline.matchDefaultArm'])),
        );
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

    public function test_field_initialisers_are_constants(): void
    {
        $this->assertStringContainsString('Constant expression contains invalid operations', self::compileError('src/Design/InitialisesAFieldWithACall.php'));
        $this->assertStringContainsString('New expressions are not supported in this context', self::compileError('src/Design/InitialisesAFieldWithAnObject.php'));
        $this->assertSame(['phpBaseline.parameterDefault'], self::phpstanErrors('src/Design/PromotesAFieldWithAnObject.php'));
        $this->assertSame('', self::compileError('src/Design/InitialisesFieldsWithConstants.php'));
        $this->assertSame([], self::phpstanErrors('src/Design/InitialisesFieldsWithConstants.php'));
    }

    /**
     * Pins a gap the listing records, so closing it fails this test until the listing changes too.
     */
    public function test_field_initialisers_are_constants_misses_closures(): void
    {
        $this->assertSame('', self::compileError('src/Design/InitialisesAFieldWithAClosure.php'));
        $this->assertSame([], self::phpstanErrors('src/Design/InitialisesAFieldWithAClosure.php'));
    }

    public function test_parameters_are_typed(): void
    {
        $this->assertSame(['missingType.parameter'], self::phpstanErrors('src/Design/UntypedParameter.php'));
        $this->assertSame([], self::phpstanErrors('src/Design/TypedDeclarations.php'));
    }

    public function test_return_types_are_declared_where_called_from_outside(): void
    {
        $this->assertSame(['missingType.return'], self::phpstanErrors('src/Design/UndeclaredReturnType.php'));
        $this->assertSame([], self::phpstanErrors('src/Design/TypedDeclarations.php'));
    }

    public function test_fields_are_typed(): void
    {
        $this->assertSame(['missingType.property'], self::phpstanErrors('src/Design/UntypedProperty.php'));
        $this->assertSame([], self::phpstanErrors('src/Design/TypedDeclarations.php'));
    }

    public function test_collection_types_name_their_element_type(): void
    {
        $this->assertSame(
            ['missingType.iterableValue', 'missingType.iterableValue'],
            self::phpstanErrors('src/Design/CollectionsWithoutElementTypes.php'),
        );
        $this->assertSame([], self::phpstanErrors('src/Design/TypedDeclarations.php'));
    }

    public function test_no_type_turns_checking_off(): void
    {
        $this->assertSame(['method.nonObject'], self::phpstanErrors('src/Design/UsesMixed.php'));
        $this->assertSame([], self::phpstanErrors('src/Design/TestsMixed.php'));
    }

    public function test_no_cast_asserts_an_unchecked_type(): void
    {
        $this->assertSame(['cast.int'], self::phpstanErrors('src/Design/CastsMixed.php'));
        $this->assertSame(['disallowed.function'], self::phpstanErrors('src/Design/AssertsAType.php'));
        $this->assertSame([], self::phpstanErrors('src/Design/TestsMixed.php'));
    }

    /**
     * Pins a gap the listing records, so closing it fails this test until the listing changes too.
     */
    public function test_no_cast_asserts_an_unchecked_type_misses_annotations(): void
    {
        $this->assertSame([], self::phpstanErrors('src/Design/ClaimsATypeUntested.php'));
    }

    public function test_no_code_is_run_from_text(): void
    {
        $this->assertSame(['disallowed.eval'], self::phpstanErrors('src/Design/RunsCodeFromText.php'));
    }

    public function test_nothing_is_called_by_a_computed_name(): void
    {
        $this->assertSame(['disallowed.function', 'disallowed.function'], self::phpstanErrors('src/Design/CallsByName.php'));
    }

    /**
     * Pins a gap the listing records, so closing it fails this test until the listing changes too.
     */
    public function test_nothing_is_called_by_a_computed_name_misses_names_no_function_takes(): void
    {
        $this->assertSame([], self::phpstanErrors('src/Design/CallsByComputedName.php'));
    }

    public function test_visibility_is_not_bypassed(): void
    {
        $this->assertSame(
            ['disallowed.new', 'disallowed.method', 'disallowed.new', 'disallowed.method', 'disallowed.method', 'disallowed.method', 'disallowed.method'],
            self::phpstanErrors('src/Design/BypassesVisibility.php'),
        );
        $this->assertSame(
            ['disallowed.method', 'disallowed.method'],
            self::phpstanErrors('src/Design/BypassesVisibilityThroughTheClass.php'),
        );
    }

    /**
     * Pins a gap the listing records, so closing it fails this test until the listing changes too.
     */
    public function test_visibility_is_not_bypassed_misses_array_casts(): void
    {
        $this->assertSame([], self::phpstanErrors('src/Design/ReadsPrivatePropertiesByCast.php'));
    }

    public function test_no_standard_library_or_third_party_code_builds_core_types(): void
    {
        $this->assertSame(
            ['disallowed.method', 'disallowed.method', 'disallowed.function'],
            self::phpstanErrors('src/Adapters/Driven/FillsObjectsFromRows.php'),
        );
        $this->assertSame(
            ['disallowed.classConstant', 'disallowed.classConstant'],
            self::phpstanErrors('src/Adapters/Driven/FetchesRowsAsAClass.php'),
        );
        $this->assertSame(
            ['disallowed.method', 'disallowed.function', 'disallowed.function'],
            self::phpstanErrors('src/Design/BuildsWithoutAConstructor.php'),
        );
        $this->assertSame([], self::phpstanErrors('src/Design/RevivesPlainData.php'));
    }

    public function test_collection_operations_take_pure_functions(): void
    {
        $this->assertSame(['disallowed.function', 'disallowed.function'], self::phpstanErrors('src/Design/WalksACollection.php'));
    }

    public function test_fake_code_references_only_ports_values_and_fakes(): void
    {
        $this->assertSame([], self::layerViolations('src/Fakes/FakeClock.php'));
        $this->assertSame(['Fake -> Orchestrator'], self::layerViolations('src/Fakes/FakeImportsOrchestrator.php'));
        $this->assertSame(['Fake -> DrivenAdapter'], self::layerViolations('src/Fakes/FakeImportsAdapter.php'));
    }

    public function test_only_test_code_references_fake_code(): void
    {
        $this->assertSame(['Orchestrator -> Fake'], self::layerViolations('src/Orchestrators/UsesFake.php'));
        $this->assertSame(['DrivenAdapter -> Fake'], self::layerViolations('src/Adapters/Driven/FallsBackToFake.php'));
        $this->assertSame([], self::layerViolations('src/Testing/ClockAssertion.php'));
    }

    public function test_no_mocking_libraries(): void
    {
        $this->assertSame(array_fill(0, 4, 'disallowed.method'), self::phpstanErrors('tests/MocksAPort.php'));
        $this->assertSame(array_fill(0, 3, 'disallowed.method'), self::phpstanErrors('tests/MocksAPortInOtherWays.php'));
        $this->assertContains('disallowed.class', self::phpstanErrors('tests/UsesMockery.php'));
        $this->assertContains('disallowed.class', self::phpstanErrors('tests/UsesProphecy.php'));
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

    /**
     * No clause asks for this: it is a setting of the baseline's own, which a project may not turn
     * off (baseline-settings-are-not-weakened).
     */
    public function test_a_method_that_overrides_another_says_so(): void
    {
        $this->assertSame(['method.missingOverride'], self::phpstanErrors('src/Design/OverridesWithoutTheAttribute.php'));
        $this->assertSame([], self::phpstanErrors('src/Adapters/Driven/SystemClock.php'));
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

        return array_column(self::$phpstanErrors[$file] ?? [], 'identifier');
    }

    /**
     * @return list<string>
     */
    private static function phpstanMessages(string $file): array
    {
        self::$phpstanErrors ??= self::runPhpstan();

        return array_column(self::$phpstanErrors[$file] ?? [], 'message');
    }

    /**
     * The error PHP gives when it compiles a file of the fixture, without running it, or '' when
     * it compiles.
     */
    private static function compileError(string $file): string
    {
        $process = proc_open([PHP_BINARY, '-d', 'display_errors=stderr', '-l', $file], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, self::FIXTURE);
        self::assertIsResource($process);
        stream_get_contents($pipes[1]);
        $errors = (string) stream_get_contents($pipes[2]);

        return proc_close($process) === 0 ? '' : $errors;
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
     * @return array<string, list<array{identifier: string, message: string}>>
     */
    private static function runPhpstan(): array
    {
        $errors = [];
        foreach (self::messagesByFile(self::runTool(['vendor/bin/phpstan', 'analyse', '--no-progress', '--error-format=json'])) as $file => $messages) {
            foreach ($messages as $message) {
                $errors[self::relative($file)][] = ['identifier' => $message['identifier'] ?? '', 'message' => $message['message']];
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
        try {
            /** @var array{files: array<string, array{messages: list<array{message: string, identifier?: string}>}>} $report */
            $report = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            self::fail("The report isn't JSON ({$e->getMessage()}):\n{$json}");
        }

        return array_map(static fn (array $file): array => $file['messages'], $report['files']);
    }

    private static function relative(string $file): string
    {
        $prefix = realpath(self::FIXTURE).'/';

        return str_starts_with($file, $prefix) ? substr($file, strlen($prefix)) : $file;
    }
}
