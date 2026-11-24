# PHPStan rules to build

Every PHPStan rule of its own that php-ianf-strict-baseline still needs, so that each can be built and tested alone. `tests/RulesToBuildTest.php` keeps the list in step with the listing and with the rules that exist.

## Keeping the list

- **A new rule or requirement goes here first.** When a clause needs a rule the baseline doesn't have, or what a planned rule must do changes, add or change its entry in the same change. Don't build the rule in that change.
- **One rule to a session, test-first.** Turn the entry's fixtures into files under `tests/fixtures/conformance`, write the conformance test named for the clause, see it fail, then write the rule in `src/Adapters/Driving/PhpStan/` and register it in `config/phpstan.neon`.
- **Then record it.** Update the clause's claim in `src/Core/RulebookListing.php`, run `task rulebook-coverage`, and delete the entry here. The test fails while an entry names a rule that exists, or a clause the listing covers in full.
- **Only PHPStan rules.** A setting of a tool the baseline already has, a deptrac layer, a `verify` step or a new tool isn't listed here.

## An entry

The heading is the rule's class name. Each entry has these lines:

- **Clauses:** the rulebook clauses it enforces, each in backticks.
- **Reports:** what it must report.
- **Allows:** what it must not report.
- **Fixtures:** the code to prove it with: the clauses' violations, and the code it must pass.
- **Why no existing tool:** why PHPStan, spaze/phpstan-disallowed-calls and deptrac don't reach it.
- **Needs first:** what must exist or be decided before it can be built, if anything.
- **Specified in:** another document that already says what the rule must do. An entry with this line may leave out the four lines it replaces.

A rule that applies to some roles takes their directories as a parameter, as `FunctionLengthRule` takes `phpIanfStrictBaselineSizeLimitedPaths`, until the role classifier gives it roles.

## Ready to build

### ThrowRule

- **Clauses:** `core-throws-nothing`, `orchestrator-code-throws-nothing`
- **Reports:** every `throw`, as a statement or an expression, in a file under `src/Core/` or `src/Orchestrators/`, one that follows an exhaustive `match` included.
- **Allows:** a `throw` in adapter and entrypoint code. A failure PHP itself throws, such as a call on null, isn't a statement and can't be reported.
- **Fixtures:** the clauses' violations in their `needs-nothing` case, and a driven adapter that throws.
- **Why no existing tool:** spaze/phpstan-disallowed-calls bans calls and references, not a statement.
- **Specified in:** code-rulebook `TO-IMPLEMENT.md`, "Errors: catching and throwing by role", for the orchestrators' half.

### CatchInOrchestratorRule

- **Clauses:** `orchestrator-code-catches-nothing`
- **Reports:** every `catch` clause in a file under `src/Orchestrators/`.
- **Allows:** `try` with only `finally`.
- **Fixtures:** the clause's violations, and an orchestrator with `try`/`finally`.
- **Why no existing tool:** spaze/phpstan-disallowed-calls bans calls and references, not a statement.
- **Specified in:** code-rulebook `TO-IMPLEMENT.md`, "Errors: catching and throwing by role".

### EntrypointCatchAllRule

- **Clauses:** `entrypoint-catches-what-escapes`
- **Reports:** a script in `bin/` or `public/` whose top-level code isn't inside one `try` with a `catch (\Throwable)`, or whose catch doesn't end with a failing status: `exit` with a non-zero status in `bin/`, a 500 response in `public/`.
- **Allows:** `declare`, `use` and the autoloader's `require` before the `try`.
- **Fixtures:** the clause's violations, and a script that wraps its body and exits 1.
- **Why no existing tool:** no tool reads the shape of a script's top-level code.
- **Specified in:** code-rulebook `TO-IMPLEMENT.md`, "Errors: catching and throwing by role".

### PortThrowsTagRule

- **Clauses:** `port-methods-return-expected-failures`
- **Reports:** a `@throws` tag on a method of an interface under `src/Ports/`.
- **Allows:** a `@throws` tag anywhere else.
- **Fixtures:** the clause's second violation, which is `Ports/DocumentsAnException.php`, and `Ports/Orders.php`, which has none. The first violation, an implementation that throws, is for PHPStan's checked exceptions, a setting, and `CheckedThrowsTagRule`.
- **Why no existing tool:** PHPStan checks that a `@throws` tag is true, not that an interface has none.
- **Specified in:** code-rulebook `TO-IMPLEMENT.md`, "Errors: catching and throwing by role".

### FailureBranchRule

- **Clauses:** `failure-branches-act`
- **Reports:** an empty branch taken on a check that a call's result is a failure, which is an `instanceof` test of a sealed type's failure variant, or `=== null` on a nullable result; and such a branch that returns a success variant from a function whose own return type has a failure variant.
- **Allows:** a branch that does anything else, a substituted value among them.
- **Fixtures:** the clause's violations that PHP can write, and a branch that creates the record that wasn't found.
- **Why no existing tool:** PHPStan's `#[\NoDiscard]` check stops at whether the result was used.
- **Specified in:** code-rulebook `TO-IMPLEMENT.md`, "Errors: failures handled, discarded and reported".

### ExplicitDiscardRule

- **Clauses:** `explicit-discards-give-a-reason`
- **Reports:** a `(void)` cast of a call, with no comment on its line or the line above.
- **Allows:** a `(void)` cast with a comment.
- **Fixtures:** the clause's second violation, and a commented discard.
- **Why no existing tool:** PHPStan accepts every `(void)` cast as a deliberate discard.
- **Specified in:** code-rulebook `TO-IMPLEMENT.md`, "Errors: failures handled, discarded and reported".

### NoDiscardAttributeRule

- **Clauses:** `returned-failures-are-used`
- **Reports:** a function or method whose declared return type is, or includes, a sealed type, and that isn't marked `#[\NoDiscard]`.
- **Allows:** a method that overrides or implements one declared outside the project; a function that returns no sealed type.
- **Fixtures:** an unmarked method that returns a `Result`, whose dropped call PHPStan then reports, and a marked one. `DropsUnmarkedSentinel.php`, which drops a built-in's sentinel, stays unreported and stays the pinned gap.
- **Why no existing tool:** PHPStan checks calls to functions that carry the attribute, and nothing makes a function carry it.

### SentinelTypeRule

- **Clauses:** `sentinels-are-null-or-a-found-flag`
- **Reports:** a function or method whose declared return type is a union with `false`, in the native type or in PHPDoc.
- **Allows:** a nullable return type; `bool`; a method that overrides or implements one declared outside the project.
- **Fixtures:** the clause's third violation, `Order|false`, and a method that returns `?Order`. A sentinel such as `-1` or `''` can't be told from a value, and stays the gap.
- **Why no existing tool:** `Order|false` is a valid type, so PHPStan accepts it.

### BooleanDeclarationRule

- **Clauses:** `no-boolean-parameters`, `no-boolean-fields`
- **Reports:** a parameter or property whose type is `bool`, `?bool` or a collection of them, in the native type or in PHPDoc.
- **Allows:** a boolean return type; a plain-data type's constructor and properties, where a plain-data type is a class with a public constructor, only readonly properties and no other method; a method that overrides or implements one declared outside the project.
- **Fixtures:** both clauses' violations, a predicate that returns `bool`, and a plain-data class with a boolean property.
- **Why no existing tool:** spaze/phpstan-disallowed-calls bans calls and references, not a type in a declaration.

### NullableCoreFieldRule

- **Clauses:** `core-type-fields-are-not-nullable`
- **Reports:** a property of a class under `src/Core/` whose type is nullable, or a collection whose element type is nullable.
- **Allows:** a nullable parameter of a factory method; a nullable return type; properties outside `src/Core/`.
- **Fixtures:** the clause's violations that PHP can write, and a factory method that takes `?string` and returns a failure for null.
- **Why no existing tool:** no tool restricts nullability by where a class is.

### DeclaredHookRule

- **Clauses:** `field-access-runs-no-code`, `conversions-run-no-code`, `only-the-constructor-is-a-magic-method`, `iteration-runs-no-code`, `no-hooks-reached-through-catch-all-parameters`, `nothing-runs-when-a-value-is-collected`, `types-declare-every-method-they-have`
- **Reports:** a class, enum or interface that declares a magic method other than `__construct`; a property with a hook; a `use` of a trait; and one that implements `ArrayAccess`, `Stringable`, `Iterator`, `IteratorAggregate` or `JsonSerializable`. Its message names the method with its own name to write instead.
- **Allows:** `__construct`; a method that yields, called by name; `Countable`; the methods an exception class inherits from PHP.
- **Fixtures:** each clause's PHP violations, and a class with a `toText()` method and a method that returns a generator. `MatchesSignature.php` implements `JsonSerializable` and needs another interface.
- **Why no existing tool:** nothing reports a method by the name it is declared with, and no setting of PHPStan bans traits or property hooks.

### TupleTypeRule

- **Clauses:** `no-positional-groups`
- **Reports:** a parameter, return or property type that is a tuple (`array{int, int}`), an array shape with integer keys, or a nullable or collection form of one; and a function declared to return `list<T>` that returns an array literal of fixed length.
- **Allows:** a property of a plain-data type, told as in `BooleanDeclarationRule`; a method that overrides or implements one declared outside the project; a `list<T>` built by a call or a loop.
- **Fixtures:** the `tuples` case's violations that PHP can write, and a function that returns a named type with two fields.
- **Why no existing tool:** a tuple is a valid type, so PHPStan accepts it.

### UnknownTypeRule

- **Clauses:** `unknown-types-stay-in-adapter-and-entrypoint-code`
- **Reports:** in files under `src/Core/`, `src/Ports/` and `src/Orchestrators/`, a parameter, return value, property or local variable whose declared type is `mixed`, or an array or iterable whose key or value type is `mixed`.
- **Allows:** a template type; the same declarations in adapter and entrypoint code.
- **Fixtures:** the clause's violations that PHP can write, a generic function with `@template T`, and a driven adapter that takes `array<string, mixed>`.
- **Why no existing tool:** PHPStan at level 10 makes code test a `mixed` before using it, and lets one be declared anywhere.

### FinalClassRule

- **Clauses:** `classes-are-final`
- **Reports:** a class that isn't declared `final`, and a class declared `abstract`.
- **Allows:** interfaces, enums and anonymous classes, which nothing can extend.
- **Fixtures:** the `supported` case's violations, and a final class that extends `TestCase`. `Base.php` declares a class that isn't final and an abstract one, for `InheritanceRule`'s fixtures, and each would be reported too.
- **Why no existing tool:** PHPStan reports a class that extends a final one, not a class that isn't final.

### ReadonlyPropertyRule

- **Clauses:** `fields-are-declared-read-only`, `fields-are-assigned-only-at-construction`, `field-collections-are-not-changed`, `core-does-nothing-impure`, `orchestrator-code-performs-no-effects`
- **Reports:** outside `src/Adapters/`, a property that is neither declared `readonly` nor in a `readonly` class. PHP and PHPStan then reject every later write to it, an element of an array it holds included, which is the held state the two purity clauses' gaps name.
- **Allows:** properties in adapter code, whose roles may hold state; static properties, which are `StaticStateRule`'s.
- **Fixtures:** the three field clauses' violations, the purity clauses' cache kept in a field, and a driven adapter with a property it changes. A readonly property that holds an object of a class that isn't immutable is `ImmutableTypeRule`'s.
- **Why no existing tool:** PHPStan reports a write to a readonly property, and nothing makes a property readonly.

### ParameterReassignmentRule

- **Clauses:** `parameters-are-not-reassigned`
- **Reports:** an assignment, a compound assignment, an increment or a decrement whose target is a parameter of the enclosing function, method or closure, or a parameter a closure captured by reference.
- **Allows:** a by-reference parameter that a method must declare to match one declared outside the project.
- **Fixtures:** the clause's violations, and a function that assigns a parameter's changed value to a new local.
- **Why no existing tool:** PHPStan has no rule on reassignment.

### ReferenceParameterRule

- **Clauses:** `no-reference-parameters`
- **Reports:** a parameter declared by reference, on a function, method, closure or interface method; and a call that passes one of the function's own parameters to a by-reference parameter, such as `sort($items)`.
- **Allows:** a by-reference parameter that a method must declare to match one declared outside the project.
- **Fixtures:** the `supported` case's violations, and a function that sorts a copy.
- **Why no existing tool:** PHPStan checks that a by-reference argument is a variable, not that a parameter isn't by reference.

### ArgumentMutationRule

- **Clauses:** `arguments-are-not-mutated`
- **Reports:** a write to a property of a parameter, or of an object reached from one, and a call on a parameter of a method that writes its own properties.
- **Allows:** a write to an element of an array parameter, which changes the function's own copy and is `ParameterReassignmentRule`'s; a method that must match one declared outside the project.
- **Fixtures:** the clause's violations that PHP can write, and a function that returns a changed copy from a factory method.
- **Why no existing tool:** PHPStan reports a write to a readonly property only, and `ReadonlyPropertyRule` leaves adapter code's properties writable.

### LocalReassignmentRule

- **Clauses:** `core-locals-are-not-reassigned-once-read`
- **Reports:** in files under `src/Core/`, an assignment to a local variable that some path has already read.
- **Allows:** a local assigned on several paths before any reads it; a loop-carried variable, assigned only before a loop and inside it; a variable that holds only a failure, assigned again after the last was checked.
- **Fixtures:** the `unsupported` case's violations, and one function for each of the three allowances.
- **Why no existing tool:** PHPStan tracks a variable's type along each path, not whether it was read before it was assigned.

### ConstructorBodyRule

- **Clauses:** `constructors-only-store-their-arguments`
- **Reports:** a constructor whose body has any statement but `$this->property = $parameter;`, with the parameter unchanged.
- **Allows:** promoted properties with an empty body; a call to `parent::__construct()` with the parameters unchanged, in a class that extends an allowed base.
- **Fixtures:** the clause's violations that PHP can write, and a constructor of promoted properties.
- **Why no existing tool:** no tool reads what a constructor's body does.

### SealedVariantNamespaceRule

- **Clauses:** `sealed-variants-share-their-unit-of-code`
- **Reports:** a class that a `phpstan-sealed` tag lists and that is in a different namespace from the sealed type.
- **Allows:** variants in the sealed type's namespace, in its file or in files beside it.
- **Fixtures:** the clause's violation, and `Families.php`.
- **Why no existing tool:** PHPStan checks that a subtype is listed, not where it is declared.

### OpenInterfaceMatchRule

- **Clauses:** `open-interfaces-are-not-matched-by-implementation`
- **Reports:** an `instanceof` test, in a condition or a `match (true)` arm, of a value whose type is an interface the project declares with no `phpstan-sealed` tag, against a class that implements it.
- **Allows:** a test against a sealed type's variant; a test of a `Throwable`; a test of a value of unknown type in adapter code, which narrows it.
- **Fixtures:** the clause's violations, and `Exhaustive.php`, whose `open()` method tests an open interface against `Countable`, which it doesn't extend, and passes.
- **Why no existing tool:** PHPStan checks that an `instanceof` can be true, not whether the family is open.

### SealedPortRule

- **Clauses:** `no-port-is-sealed`
- **Reports:** an interface under `src/Ports/` with a `phpstan-sealed` tag.
- **Allows:** sealed types anywhere else.
- **Fixtures:** the clause's violation, and `Ports/Clock.php`.
- **Why no existing tool:** PHPStan enforces a sealed tag wherever it is, and doesn't say where one may not be.

### ComputedNameRule

- **Clauses:** `nothing-is-called-by-a-computed-name`, `nothing-is-imported-by-a-computed-path`
- **Reports:** a call of a variable function (`$name()`), a method or static method by a name in a variable, `new $class`, a string or an array of a class and a method name passed where a callable is expected, and an `include` or `require` whose path isn't a literal or a constant expression.
- **Allows:** a first-class callable (`trim(...)`); a closure; a call on a variable that holds a `Closure`; `require __DIR__.'/…'`.
- **Fixtures:** both clauses' PHP violations, of which `CallsByComputedName.php` has four, and one function for each allowance. `call_user_func` and `call_user_func_array` are a setting of spaze/phpstan-disallowed-calls, which `CallsByName.php` proves.
- **Why no existing tool:** spaze/phpstan-disallowed-calls bans a function by its name, and these calls have none.
- **Specified in:** code-rulebook `docs/plans.md`, "Escape hatches", which names the candidates.

### TypeClaimRule

- **Clauses:** `no-cast-asserts-an-unchecked-type`
- **Reports:** an inline `@var` tag on a variable or an expression, and a `@phpstan-assert`, `@phpstan-assert-if-true` or `@phpstan-assert-if-false` tag on a function.
- **Allows:** a `@var` tag on a property or a constant, which declares its type; `instanceof` and the `is_*` tests.
- **Fixtures:** the clause's PHP violations, of which `ClaimsATypeUntested.php` has two, and `TestsMixed.php`. `assert()` is a setting of spaze/phpstan-disallowed-calls, which `AssertsAType.php` proves.
- **Why no existing tool:** PHPStan trusts an inline `@var` and an assert tag unless it can prove them wrong.

### EmptyWaitRule

- **Clauses:** `nothing-sleeps`
- **Reports:** a call that waits with nothing but a duration to wait for: `stream_select()` or `socket_select()` given three empty arrays and a timeout.
- **Allows:** a wait that also names a stream, a socket or a signal.
- **Fixtures:** the clause's third violation, and a wait on a stream with a timeout. The sleep functions are a setting of spaze/phpstan-disallowed-calls, banned in every directory.
- **Why no existing tool:** spaze/phpstan-disallowed-calls can ban `stream_select()`, and not by what it is asked to wait for.

### OrchestratorLoopRule

- **Clauses:** `orchestrator-code-runs-no-loop-until-stopped`
- **Reports:** in files under `src/Orchestrators/`, `while (true)`, `for (;;)`, `do … while (true)`, and a loop whose condition is a call on a port.
- **Allows:** a `foreach`; a loop whose condition tests a value a function in `src/Core/` returned, such as reading pages until the last.
- **Fixtures:** the clause's violations, and an orchestrator that reads pages until the core says it has them all. A loop over pieces of work that could each fail alone isn't in the code, and stays the gap.
- **Why no existing tool:** no tool reports a loop by what ends it.

### PortMethodNameRule

- **Clauses:** `port-methods-need-no-call-order`
- **Reports:** a method of an interface under `src/Ports/` named `begin`, `commit`, `rollback`, `open`, `close`, `init`, `connect`, `start`, `end` or `flush`.
- **Allows:** every other name. A method that fails unless another was called first isn't in its name, and stays the gap.
- **Fixtures:** the clause's first, second, third and fifth violations, and `Ports/Clock.php`.
- **Why no existing tool:** no tool bans a method by its name.
- **Specified in:** code-rulebook `docs/plans.md`, "Changes and call order".

### PortReturnTypeRule

- **Clauses:** `port-methods-return-no-interfaces`
- **Specified in:** code-rulebook `docs/plans.md`, "Changes and call order".

### FactoryMethodShapeRule

- **Clauses:** `core-types-have-factory-functions`, `core-types-are-built-only-by-their-factory-functions`
- **Specified in:** code-rulebook `docs/plans.md`, "Factory-method shape (PHP)".

### OneConstructionSiteRule

- **Clauses:** `one-factory-function-calls-each-constructor`
- **Specified in:** code-rulebook `docs/plans.md`, "One construction site".

### IdentityComparisonRule

- **Clauses:** `references-are-not-compared-by-identity`, `collections-are-not-keyed-by-instance`
- **Specified in:** code-rulebook `docs/plans.md`, "Identity comparison".

### ImmutableTypeRule

- **Clauses:** `values-have-immutable-types`, `collection-types-are-immutable`, `built-values-are-not-changed`
- **Specified in:** code-rulebook `docs/plans.md`, "Immutable types" and "Changes and call order".

### OrchestratorTestCallRule

- **Clauses:** `orchestrator-test-case-calls-one-use-case-once`
- **Reports:** a test method of a class under `tests/Orchestrators/` that makes no call, or more than one, to a public method of a class under `src/Orchestrators/`, or makes one inside a loop.
- **Allows:** any number of calls on fakes and on the core.
- **Fixtures:** the clause's violations, and a test that sets a fake, calls once and checks the fake. A call made through a helper method isn't counted, and stays the gap.
- **Why no existing tool:** nothing counts a test's calls by what they are calls to.
- **Specified in:** code-rulebook `TO-IMPLEMENT.md`, "Testing: fakes, contract tests, single calls and mutation testing".

### ObjectArrayCastRule

- **Clauses:** `visibility-is-not-bypassed`
- **Reports:** an `(array)` cast of an expression whose type is, or may be, an object, which gives the object's private and protected properties under mangled keys.
- **Allows:** an `(array)` cast of a value that can't be an object, such as a string or null.
- **Fixtures:** `ReadsPrivatePropertiesByCast.php`, and a function that casts a `string|null` to an array. Reflection and closure binding are settings of spaze/phpstan-disallowed-calls, which `BypassesVisibility.php` and `BypassesVisibilityThroughTheClass.php` prove.
- **Why no existing tool:** spaze/phpstan-disallowed-calls bans calls and references, not a cast, and PHPStan accepts a cast of an object to an array.

### PropertyDefaultClosureRule

- **Clauses:** `field-initialisers-are-constants`
- **Reports:** a property, static or not, whose default is a closure or a first-class callable, which PHP compiles from 8.5 and which builds a `Closure`.
- **Allows:** a default that is a literal, a constant, an enum case or an expression of them; a property with no default.
- **Fixtures:** `InitialisesAFieldWithAClosure.php`, and `InitialisesFieldsWithConstants.php`. PHP itself rejects a call or a `new` in a property's default, which `InitialisesAFieldWithACall.php` and `InitialisesAFieldWithAnObject.php` prove, and `ParameterDefaultRule` reports a promoted property's.
- **Why no existing tool:** PHP 8.5 made a closure a constant expression, PHPStan accepts one as a default, and spaze/phpstan-disallowed-calls bans calls and references, not an expression by where it is.

### CatchAllRule

- **Clauses:** `driving-adapter-code-catches-everything-only-around-invocations`, `driven-adapter-code-converts-expected-failures`
- **Reports:** `catch (\Throwable)` and `catch (\Exception)` in files under `src/Adapters/Driven/`, and in files under `src/Adapters/Driving/` except in the one method the parameter `phpIanfStrictBaselineInvocationCatchAll` names.
- **Allows:** a catch of a narrower exception class; a catch in the named method. With the parameter left empty, as the baseline ships it, no catch of either class is allowed, which is right for a project whose only invocation is a console command's run.
- **Fixtures:** the first clause's violations, a driven adapter that catches `\Throwable`, a driven adapter that catches its driver's unique-constraint exception, and a catch-all in the method the fixture's `phpstan.neon` names.
- **Why no existing tool:** spaze/phpstan-disallowed-calls bans calls and references, not a statement.
- **Needs first:** nothing. Decided 2026-11-12: a project marks its invocation catch-all by naming one method, as `Class::method`, in the parameter `phpIanfStrictBaselineInvocationCatchAll` of its `phpstan.neon`, which is empty by default. It is a setting and not an attribute, so that no function can mark itself and the effective-settings comparison sees it.
- **Specified in:** code-rulebook `TO-IMPLEMENT.md`, "Errors: catching and throwing by role".

### FunctionComplexityRule

- **Clauses:** `adapter-code-function-size-is-limited`
- **Reports:** a function, method or closure in the directories `FunctionLengthRule` reads whose cognitive complexity is over 10. The rule computes it itself, as gocognit does for go-ianf-strict-baseline: one for each `if`, `elseif`, `else`, `switch`, `match`, loop, `catch`, ternary and run of one boolean operator, and one more for each level a branch or a loop is nested in.
- **Allows:** a function at or under 10; a flat `match` of any number of arms, which counts one.
- **Fixtures:** a short adapter method of complexity 11, a method with a flat `match` of twelve arms, and `Long.php`, which `FunctionLengthRule` already reports.
- **Why no existing tool:** PHPStan has no complexity rule, and one from a third party is a dependency to vet.
- **Needs first:** nothing. Decided 2026-11-12: the measure is cognitive complexity and the limit is 10, go-ianf-strict-baseline's, computed by the baseline's own rule and not by a new dependency.

### PortImplementedInTestsRule

- **Clauses:** `only-driven-adapters-and-fakes-stand-in-for-ports`, `no-hand-written-scripted-doubles`
- **Reports:** a class, named or anonymous, that implements an interface under `src/Ports/` and is neither under `src/Adapters/Driven/` nor under `src/Fakes/`, and a class that extends a fake.
- **Allows:** the one fake for each port, and driven adapters.
- **Fixtures:** the first clause's PHP violations, the second clause's three, and a test that builds the port's fake.
- **Why no existing tool:** deptrac reads `src/` and `bin/`, and reports references between layers, not what implements an interface.
- **Needs first:** nothing. Decided 2026-11-12: fakes live in `src/Fakes/`, in the namespace `IanFlanagan\<Project>\Fakes\`, so that a project's tests can load the fakes of a first-party dependency's ports, which `autoload-dev` wouldn't give them; and a fake is named `Fake` followed by its port's name (`Orders` → `FakeOrders`). The package's own `tests/Orchestrators/VerifyTest.php` implements `ProjectFiles` with an anonymous class, which this rule will report.
- **Specified in:** code-rulebook `TO-IMPLEMENT.md`, "Testing: fakes, contract tests, single calls and mutation testing".

### FakePerPortRule

- **Clauses:** `every-port-has-one-fake`
- **Reports:** an interface under `src/Ports/` that no class under `src/Fakes/` implements, or that more than one does; and a class under `src/Fakes/` that implements a port and isn't named `Fake` followed by the port's name.
- **Allows:** a class under `src/Fakes/` that implements no port, which is a helper only fakes use, such as an in-memory table.
- **Fixtures:** the clause's violations, and `Ports/Clock.php` with a `FakeClock` under `src/Fakes/`.
- **Why no existing tool:** nothing counts the classes that implement an interface, or checks one's name against another's.
- **Needs first:** nothing. Decided 2026-11-12, as for `PortImplementedInTestsRule`: fakes live in `src/Fakes/`, and a fake's name is `Fake` followed by its port's.
- **Specified in:** code-rulebook `TO-IMPLEMENT.md`, "Testing: fakes, contract tests, single calls and mutation testing".

### ContractTestsRule

- **Clauses:** `every-port-has-one-set-of-contract-tests`, `fake-and-adapter-tests-run-the-contract-tests`
- **Reports:** an interface under `src/Ports/` with no final class `tests/Contracts/<Port>Contract.php` that declares a public static `cases()` method; a contract class that references a class under `src/Fakes/` or `src/Adapters/Driven/`; and a test class of a fake or a driven adapter that implements the port, at the same path under `tests/`, with no method marked `#[DataProvider]` that returns `<Port>Contract::cases()`.
- **Allows:** a contract class that references the port and the core; other tests of its own in a fake's or an adapter's test class.
- **Fixtures:** both clauses' violations, and a `ClockContract` whose cases the tests of `FakeClock` and `SystemClock` each run.
- **Why no existing tool:** nothing pairs a port with a test class, or reads what a data provider returns. PHPStan must be given `tests/` for the rule to read it.
- **Needs first:** nothing. Decided 2026-11-12, the form contract tests take without a base class or a trait: one final class for each port, `tests/Contracts/<Port>Contract.php`, whose public static `cases()` returns the contract's cases by name, each a closure that takes the port and asserts on it. The test class of the fake and of each driven adapter declares a `#[DataProvider]` method that returns `<Port>Contract::cases()`, and one test method that builds a new, empty implementation and passes it to the case.
- **Specified in:** code-rulebook `TO-IMPLEMENT.md`, "Testing: fakes, contract tests, single calls and mutation testing".

## Waiting on a decision

### CheckedThrowsTagRule

- **Clauses:** `core-catches-expected-failures-it-calls`, `port-methods-return-expected-failures`, `driven-adapter-code-converts-expected-failures`
- **Reports:** a `@throws` tag that names a checked exception, by PHPStan's own settings, on a function or method in a file under `src/Core/`, and on a method of a class under `src/Adapters/Driven/` that implements a method of an interface under `src/Ports/`. With PHPStan's `missingCheckedExceptionInThrows`, which the baseline sets, no checked exception can then escape either unreported.
- **Allows:** a `@throws` tag that names an unchecked exception, which explains when a failure no caller could handle is thrown; a tag on any other function.
- **Fixtures:** `Adapters/Driven/DeclaresWhatItThrows.php` and `Core/DeclaresAConversionsException.php`, which pass today, and `Adapters/Driven/ConvertsTheFailure.php` and `Core/CatchesAConversionsFailure.php`. An exception that extends `RuntimeException`, as `PDOException` does, is unchecked and stays the gap, which `Adapters/Driven/ThrowsAnUncheckedException.php` pins.
- **Why no existing tool:** PHPStan's `throwTypeCovariance` compares an implementation's `@throws` tag with its interface's only when the interface's method has one. A port method tagged `@throws void` does get an implementation's tag reported, and its explicit `throw` of an unchecked exception too, which the port's implementations must stay free to write.
- **Needs first:** what the core does about a checked exception that can't happen. `RulebookListing::json()`, in this package's own core, declares `@throws \JsonException` for an encoding that can't fail, and this rule would report it; the core may not catch it and throw another in its place (`core-throws-nothing`), so either the encoding moves to adapter code or the rule allows a tag under some condition.

## Waiting on the role classifier

### RoleClassifierRule

- **Clauses:** `every-declaration-has-one-role`, `a-unit-of-code-holds-one-role`, `every-type-has-one-shape`, `dependency-declarations-keep-their-role`
- **Specified in:** code-rulebook `docs/plans.md`, "Roles in each baseline", which gives its tests in order, its fixtures and what it reports. It is PHPStan collectors read by one rule, and the rules below take each declaration's role from it.

### RoleReferenceRule

- **Clauses:** `driven-adapter-code-references-only-ports-and-values`, `value-code-references-only-value-code`, `ports-reference-only-value-code`, `other-roles-reach-only-members`, `entrypoint-code-calls-only-driving-adapters-and-values`, `only-adapter-and-entrypoint-code-references-plain-data-types`, `error-types-of-other-roles-are-not-matched`, `port-units-of-code-follow-interface-implementation`
- **Reports:** a reference or a call from one declaration to another whose role the vocabulary's `may-reference` and `may-call` don't allow the first: decision code named from value, port or driven-adapter code; a helper named from another role's code; a call from entrypoint code to an orchestrator, a driven adapter or decision code; a plain-data type named outside adapter and entrypoint code; and a `catch` or `instanceof` of an exception class that is another role's helper.
- **Allows:** every reference and call the vocabulary lists; entrypoint code referencing a helper to build it; a match on an exception PHP or a third party declares; a driving adapter's reference to a system adapter the vocabulary says it also calls.
- **Fixtures:** the clauses' violations, the vocabulary's example for each allowed reference, and the fixtures deptrac's layers pass and fail today, since this rule replaces them.
- **Why no existing tool:** deptrac's layers come from directories and can't tell a decision from a value or a helper from a member, and it has no layer for a script.
- **Needs first:** `RoleClassifierRule`.

### SignatureTypeRule

- **Clauses:** `port-methods-take-and-return-signature-types`, `orchestrator-takes-and-returns-signature-types`, `decisions-driving-adapters-call-take-and-return-signature-types`, `factory-functions-take-signature-types`, `orchestrator-is-given-ports`, `the-core-takes-no-plain-data`
- **Reports:** a parameter or result that isn't a signature type, on a port's method, an orchestrator's public method, a decision function or method that driving-adapter code calls, and a parameter of a core type's factory method; and a constructor parameter of an orchestrator that is none of a port, another orchestrator and a signature type.
- **Allows:** a core type; `int`, `float` and `string`; `bool` as a result; a nullable or list form of one; no result.
- **Fixtures:** the six clauses' violations that PHP can write, and a port, an orchestrator and a factory method that take and return core types and scalars.
- **Why no existing tool:** PHPStan knows a type, and not whether it is a core type or which role's code calls a method.
- **Needs first:** `RoleClassifierRule`, for which classes are core types and which methods driving-adapter code calls.

### PureArgumentRule

- **Clauses:** `core-receives-only-pure-values`
- **Reports:** an argument to a function or method of the core, or to a core type's constructor, whose type is a port, an adapter or an orchestrator, or a closure that captures or calls one.
- **Allows:** core types, scalars, and closures over them.
- **Fixtures:** the clause's violations, and an orchestrator that passes the core what a port returned.
- **Why no existing tool:** deptrac stops the core naming a port, and not a caller passing one under `callable`, `object` or an interface the core declares.
- **Needs first:** `RoleClassifierRule`.

### DecisionResultFlowRule

- **Clauses:** `driving-adapter-code-only-reports-what-a-decision-returns`
- **Reports:** in one function of driving-adapter code, a variable set from a call to decision code that is then passed to an orchestrator or to another call to decision code, or that a condition reads when the branch it guards calls an orchestrator.
- **Allows:** building the response, the output or the exit code from it.
- **Fixtures:** the clause's violations, and a controller that calls a decision and renders its result. A value that travels through a property or a helper method escapes it, and stays the gap.
- **Why no existing tool:** no tool follows a value from one call to another by the roles of the code called.
- **Needs first:** `RoleClassifierRule`, to tell decision code from value code.

### OrchestratorBranchRule

- **Clauses:** `orchestrator-code-branches-only-on-decisions`
- **Reports:** in orchestrator code, a condition that reads a value a port's method returned, or one a core type's method derived from it, other than a test of whether the call failed: `=== null`, or an `instanceof` of a failure variant.
- **Allows:** a condition on what decision code returned.
- **Fixtures:** the clause's violations, and an orchestrator that passes what it read to a decision and branches on the answer.
- **Why no existing tool:** no tool follows a value from a call to a condition by the role of the code called.
- **Needs first:** `RoleClassifierRule`, to tell a core type's method from a decision.

## Waiting on something else

### LoopShapeRule

- **Clauses:** `loops-compute-no-value-an-operation-names`
- **Needs first:** PHP's shared collection operations, which the loops are replaced with.
- **Specified in:** code-rulebook `docs/plans.md`, "Loop-shape check".

### CollectionCallbackRule

- **Clauses:** `collection-operations-take-pure-functions`, `folds-compute-no-value-a-narrower-operation-names`
- **Reports:** a function passed to a shared collection operation that calls a method of a port, an orchestrator or an adapter, performs a banned effect, or assigns to a variable it captured by reference; and a function passed to the shared `fold` whose body only appends, filters, adds, groups, keeps a least or greatest element, or sets a boolean.
- **Allows:** a pure function; a fold whose step reads the value so far, such as a running balance.
- **Fixtures:** both clauses' violations, and a fold that merges overlapping intervals. `array_walk()`, `array_walk_recursive()` and `iterator_apply()` are a setting of spaze/phpstan-disallowed-calls, which `WalksACollection.php` proves.
- **Why no existing tool:** spaze/phpstan-disallowed-calls can ban `array_walk()`, and not what a function passed to another does.
- **Needs first:** PHP's shared collection operations.
- **Specified in:** code-rulebook `docs/plans.md`, "Collection operations", which names the candidates.

### InternalValueRule

- **Clauses:** `outside-facing-code-names-no-internal-type`, `external-driving-adapters-get-no-internal-values`, `external-driving-adapters-get-no-internal-values-from-decisions`, `external-driven-adapters-take-no-internal-values`, `only-decisions-and-internal-adapters-read-internal-values`
- **Needs first:** PHP's shared ID unit-of-code, and the baseline's capability list, which says whether an adapter is internal or external.
- **Specified in:** code-rulebook `docs/plans.md`, "Identity and the schema", the internal-values checks: a reference ban, a crossing check and a call-site check, which may be three rules.

### IdTypeRule

- **Clauses:** `ids-are-held-as-their-id-types`, `each-public-id-has-a-type`
- **Reports:** a property, parameter or result that holds a row's ID or public ID as a `string`, or as a third-party UUID type, and an ID property that is nullable.
- **Allows:** an ID type built on the shared ID unit-of-code.
- **Fixtures:** both clauses' violations that are in code and not in a schema.
- **Why no existing tool:** no tool knows which strings are IDs.
- **Needs first:** PHP's shared ID unit-of-code, and a way to tell which values are IDs, such as a property named `id` or ending `Id`.
- **Specified in:** code-rulebook `docs/plans.md`, "Identity and the schema", the static checks.

### ThirdPartyReachRule

- **Clauses:** `orchestrator-code-reaches-nothing-impure`, `adapters-and-entrypoints-reach-only-their-roles-operations`, `every-third-party-dependency-is-listed`
- **Reports:** a reference to a class or function of a third-party release unit that the baseline's dependency list doesn't have, or whose entry gives it an impure operation the referencing code's role may not perform.
- **Allows:** a reference to a listed release unit whose operations the role may perform, and to PHP's own classes and functions, which the effect bans cover.
- **Fixtures:** the three clauses' violations, and an orchestrator using a listed release unit whose entry gives it no impure operation. What the core reaches is `CoreCallsReachNothingImpureRule`'s, judged from the code rather than from the list.
- **Why no existing tool:** deptrac leaves a class in no layer unchecked, and spaze/phpstan-disallowed-calls lists what is banned, not what is allowed.
- **Needs first:** the baseline's dependency list, in the form code-rulebook's `docs/baselines.md` gives.
