# php-ianf-strict-baseline

The checks every PHP project of mine shares: the architecture (a functional core, and a shell of orchestrators, driving adapters and driven adapters behind ports), the shape of its code, and its formatting. It is the PHP counterpart of [go-ianf-strict-baseline](https://github.com/ianflanagan1/go-ianf-strict-baseline) and [@ianflanagan1/ts-ianf-strict-baseline](https://github.com/ianflanagan1/ts-ianf-strict-baseline), and enforces the [code rulebook](https://github.com/ianflanagan1/code-rulebook) in PHP's terms.

| Export | What it is |
|---|---|
| `config/deptrac.yaml` | deptrac layers for the roles, by directory, and which may depend on which |
| `config/phpstan.neon` | spaze/phpstan-disallowed-calls bans on each impure operation, outside the roles allowed to perform it, and on sleeping, mocking libraries, dependency-injection containers and the calls that get past the other checks; the baseline's own PHPStan rules, in `src/Adapters/Driving/PhpStan/`; PHPStan's checked exceptions; and level 10, PHPStan's strictest |
| `config/pint.json` | The Pint settings, for a project's `pint.json` to extend |
| `php-ianf-strict-baseline verify` | Checks that the project in the working directory still follows the baseline, and exits 1 with what to change when it doesn't. So far it checks only that `.gitignore` has the line `/cache/` |
| `php-ianf-strict-baseline rulebook` | Prints how the baseline enforces each clause of the rulebook, as the coverage listing the rulebook checks |

## Roles

The rulebook gives every declaration a role from its own code. The baseline's checks don't read that yet: a file's directory decides its role.

| Role | Directory | May depend on | May perform |
|---|---|---|---|
| Decision and value, together the core | `src/Core/` | The core | Nothing |
| Port | `src/Ports/` | The core | Nothing: ports are interfaces |
| Orchestrator | `src/Orchestrators/` | The core, ports and other orchestrators | Nothing: only through ports |
| Driving adapter | `src/Adapters/Driving/` | The core, orchestrators and other driving adapters | Serving requests, files, processes, the console and global logging |
| Driven adapter | `src/Adapters/Driven/` | The core, ports and other driven adapters | Network calls, databases, files, processes, the console and global logging |
| Entrypoint | `bin/` and `public/` | Anything | Files, processes, the console, global logging and reading configuration |
| Fake | `src/Fakes/` | The core, ports and other fakes | Nothing |
| Shipped test code | `src/Testing/` | The core, ports, fakes and other shipped test code | Nothing |

The checks don't tell decision code from value code, so nothing that turns on the difference is checked, such as that a driven adapter references no decision code.

A fake lives in `src/Fakes/`, so that a project's tests can load the fakes of a dependency's ports, and is named `Fake` followed by its port's name (`Orders` → `FakeOrders`), which nothing checks. Test code that a project ships for the tests of projects that require it, such as an assertion or a port's contract tests, lives in `src/Testing/`. Composer's `autoload` loads both, and no program runs either: deptrac reports any class of a role that references one. Shipped test code is no role in the rulebook; its layer is how the checks let it reach the core while keeping every role's code from reaching it.

A project's tests may perform every operation above, and the bans on sleeping, mocking libraries and the rest of [the code rules](#code-rules) reach them too. A class under `src/` in none of these directories has no role: deptrac puts it in an Unplaced layer, which may depend on nothing and which nothing may depend on, and PHPStan reports the operations it performs. A stray class that neither depends on nor is used by a class in a role isn't caught yet.

A driving adapter, such as a controller or a console command, calls an orchestrator directly; it references no port and no driven adapter. An entrypoint constructs the adapters and orchestrators and starts a driving adapter.

A dependency of mine, in `IanFlanagan\<Project>\`, takes its roles from its namespace: `\Core\`, `\Ports\`, `\Orchestrators\`, `\Adapters\Driving\` and `\Adapters\Driven\` give those roles, `\Fakes\` and `\Testing\` the two layers above, and anything else but `\Tests\` is Unplaced. The root-namespace rule maps a project's namespace to `src/`, so the namespace and the directory give a class the same role. A project's core can then use a library's core, and its driven adapters implement a library's ports, while its core calling a library's orchestrators is reported. Other vendors' classes are in no layer.

A port is an interface in `src/Ports/`, named for what the domain needs from it (`Orders`, `Clock`), not for the vendor or technology behind it (`MysqlOrders`, `RedisCache`). Nothing checks the naming. Ports have their own directory, rather than living beside the orchestrator that calls them as in Go, because a PHP driven adapter has to name the interface it implements, and driven adapters may not import orchestrators.

The impure operations are the rulebook's, and each has its own list of the directories it is allowed in:

- **Serving requests** (`header()`, `setcookie()`, the request superglobals, a listening socket): driving adapters.
- **Network calls** (`curl_*()`, `fsockopen()`, `mail()`, `SoapClient`) and **databases** (`PDO`, `mysqli`, `pg_*()`, `SQLite3`): driven adapters. A function that reads files isn't reported when it is given a URL.
- **Files**, **processes** (including `exit`), **the console** (including `echo`) and **global logging**: both adapters and the entrypoints.
- **Reading configuration** (`getenv()`, `$_ENV`, `$_SERVER`): the entrypoints.
- **The clock and randomness**: both adapters and the entrypoints, for now. The rulebook reserves them for system adapters, which PHP's shared code will hold; until those exist, a project's own driven adapter is the only way to reach either. `new DateTimeImmutable()` reads the clock, so the core takes the current time as a value. Parsing with `DateTimeImmutable::createFromFormat()` isn't reported, though it reads the machine's local time zone when the text names no offset.
- **Global state** (`global`, `$GLOBALS`, a static property or variable): no role's code.
- **Sleeping** (`sleep()`, `usleep()` and the like): no code at all, a test included.

These lists are the one list of impure operations. Each entry that has an `allowIn` list is one, sleeping's empty; an entry without one, such as the ban on `assert()`, is a code rule. A project that adds a ban for an impure operation gives it an `allowIn` list too, so that the check below reads it.

Code the core calls is judged by what its own code does, never by which package holds it or where it lives. `CoreCallsReachNothingImpureRule` follows each call, `new` and class constant in `src/Core/` into code outside the core, a library's or the project's own, through every call that code can make, at any depth, and through the load-time code of every file it loads, the files a package's `composer.json` lists under `autoload.files` among them. It reports a call that reaches an impure operation, naming the path from the core's call to it, and a call it can't resolve, such as one through a function value or an interface the code didn't receive as an argument, since it can't show that one is pure. A project with other directories for its core sets `phpIanfStrictBaselineCorePaths`. The rule parses and walks each file it follows, so analysing a core that uses large libraries takes more memory: raise PHPStan's `--memory-limit` if a worker reaches it.

A role's directory holds its members, such as each orchestrator, and their helpers: code only that role's own classes use, such as a method two orchestrators use to page through a port's results. Keep a helper a private method of the class that uses it. The checks don't tell a helper class from a member, so nothing stops another role using one.

## Errors

An expected failure, one a caller could handle, such as a missing record, a conflicting write or invalid input, is a value a function returns: `null` only to say that the one thing asked for doesn't exist, and every other failure as a result object, even when it's the only one today, marked `#[\NoDiscard]` so PHPStan reports a caller that drops it. A function never sets aside a value of its result's own type, such as `false`, `-1` or `''`, to mean failure. An exception is for a failure no caller could handle, such as a broken invariant, and nothing between the `throw` and the catch-all where its invocation began catches it.

PHPStan's checked exceptions enforce part of this. Every exception is checked but an `Error`, a `LogicException`, a `RuntimeException`, a `Random\RandomException` and their subclasses, which are for what no caller could handle. A function that throws a checked exception, or lets one escape from a call such as `new \DateTimeZone($name)` or `json_decode()` with `JSON_THROW_ON_ERROR`, is reported unless a `@throws` tag declares it. So catch it at the call and return the failure; don't add the tag, which nothing reports yet. An unchecked exception thrown for an expected failure isn't reported either. No check enforces the rest:

- **Core.** Never `throw`. Return the failure. Where PHP or a library throws for an expected failure, such as `new \DateTimeZone()` on a name that isn't a time zone, catch that exception at the call and return the failure as a value.
- **Ports.** Each port method returns every expected failure an implementation can meet, as `null` or as a result object the core declares, and its docblock names no exception for one. Its implementations throw only for failures no caller could handle, such as a lost connection.
- **Driven adapters.** Catch the exception a library throws for an expected failure at the call that throws it, such as a unique-constraint violation, and return it through the port as a value. Catch nothing else, and never `catch (\Throwable)` or `catch (\Exception)`. Never throw for an expected failure yourself.
- **Orchestrators.** Return every expected failure, whether the core decided it or a port returned it. Never `throw`, and never `catch`. `try`/`finally` for cleanup is fine.
- **Matching errors.** A `catch` or `instanceof` names no exception that code of another role declares, in the project or in a dependency of mine. It may name one PHP or another vendor's library declares, as the core and driven adapters do to convert an expected failure. A failure another role acts on is a core type, and crosses a port or an orchestrator as a value.
- **Driving adapters.** One that starts many invocations, such as an HTTP kernel or a queue worker, runs each inside one catch-all in `src/Adapters/Driving/`: a `catch (\Throwable)` around the invocation, or the framework's exception handler. It logs what it caught with its trace, reports it to the caller as a failure, such as status 500 or a rejected message, and ends the invocation without retrying it. To a caller outside the operator's control, such as an API's client, it reports no message or trace, only an ID it also logs. Nothing else in `src/Adapters/Driving/` catches `\Throwable` or `\Exception`. A console command's only invocation is the whole run, so it leaves its failures to the entrypoint.
- **Entrypoints.** Each script in `bin/` and `public/` wraps its body in `try`/`catch (\Throwable)`, reports what it caught, and exits with a failing status: `exit(1)` in `bin/`, a 500 response in `public/`.

## Code rules

The baseline's PHPStan rules apply to every role:

- **Closed families.** Seal a family of variants with a `@phpstan-sealed A|B` tag on its interface or abstract class, so PHPStan reports a class that joins it unlisted. Branch on it with `match (true) { $x instanceof A => …, $x instanceof B => … }`, and on an enum with `match ($x)`, so PHPStan reports a match that leaves a case out. A `switch` on either, or a `default` arm in a match on either, is reported: name each case, listing those that share a branch in one arm.
- **Composition.** A class extends only a class `phpIanfStrictBaselineAllowedParentClasses` names (PHPUnit's `TestCase` by default) or one of PHP's own exceptions. Share behaviour by taking a collaborator instead.
- **Types.** At level 10, PHPStan reports a parameter, return value or property with no type, an array with no value type, and anything done with a `mixed` but testing it or passing it on.
- **Parameters.** No parameter has a default value, except in a method that overrides or implements one whose signature has it.
- **Results.** Mark a function that returns an expected failure as a value `#[\NoDiscard]`, so PHPStan reports a call that drops it.
- **Static state.** No static property or static variable outside tests.
- **Sleeping.** No `sleep()`, `usleep()`, `time_nanosleep()` or `time_sleep_until()`, in tests either. Fail the work back to whatever can wait, such as a queue that redelivers the message later.
- **Test doubles.** No mocking library, in tests either: PHPUnit's `createMock()`, `createStub()` and `getMockBuilder()`, Mockery and Prophecy are banned. Write each port's fake by hand, once.
- **Names in the source.** No `eval()`, and no `call_user_func()` or `call_user_func_array()`: call the function by name, or pass a first-class callable such as `trim(...)`.
- **Visibility.** No `ReflectionProperty` or `ReflectionMethod`, and no `Closure::bind()`, `bindTo()` or `call()`.
- **Type claims.** No `assert()`, which production skips: test the value with `instanceof` or an `is_*()` function.
- **Construction.** Nothing has PHP build an object around its constructor or its factory method: no `ReflectionClass::newInstanceWithoutConstructor()`, `fetchObject()`, `fetch_object()`, `PDO::FETCH_CLASS` or `PDO::FETCH_INTO`, and no `unserialize()` without `['allowed_classes' => false]`.
- **Collections.** No `array_walk()`, `array_walk_recursive()` or `iterator_apply()`: perform an effect for each element in a `foreach`.
- **Overrides.** A method that overrides or implements another carries `#[\Override]`.
- **Size.** Each function in an adapter or an entrypoint, and a script's top-level code there, is at most 50 lines. A project with other directories for them sets `phpIanfStrictBaselineSizeLimitedPaths`.
- **Cycles.** No class depends on itself through others. A sealed type and its variants count as one class, so they may name each other.
- **Wiring.** The entrypoint constructs each adapter and orchestrator and passes it in. The common containers (PSR-11, PHP-DI, Symfony's, Laravel's and the rest) are banned.

## Use

```sh
composer require --dev ianflanagan1/php-ianf-strict-baseline phpstan/extension-installer
```

phpstan/extension-installer includes `config/phpstan.neon` and spaze/phpstan-disallowed-calls. Without it, include both in `phpstan.neon`:

```neon
includes:
    - vendor/spaze/phpstan-disallowed-calls/extension.neon
    - vendor/ianflanagan1/php-ianf-strict-baseline/config/phpstan.neon
```

`deptrac.yaml`:

```yaml
imports:
  - vendor/ianflanagan1/php-ianf-strict-baseline/config/deptrac.yaml

deptrac:
  paths:
    - src
    - bin
```

`pint.json`, which sets no rules of its own:

```json
{
    "extend": "vendor/ianflanagan1/php-ianf-strict-baseline/config/pint.json"
}
```

The project's `check` task runs `vendor/bin/php-ianf-strict-baseline verify`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` and `vendor/bin/deptrac analyse`, and fails if any of them does. Nothing checks this yet.

The tools keep their caches under `cache/`: the shipped deptrac config puts deptrac's in `cache/deptrac/`, and Pint leaves the directory alone. Point PHPStan's and PHPUnit's there too, with `tmpDir: cache/phpstan` in `phpstan.neon`'s `parameters` and `cacheDirectory="cache/phpunit"` in `phpunit.xml`. PHPStan reads `tmpDir` only from the project's own config and the files it includes directly, so `config/phpstan.neon` can't set it for a project that uses extension-installer. `verify` fails unless `.gitignore` has the line `/cache/`.

`config/phpstan.neon` sets level 10. A project's own `phpstan.neon` sets no lower level, turns off nothing the baseline's configs turn on, ignores no error, whether in its configuration or with a `@phpstan-ignore` comment, and excludes none of its hand-written code. Nothing checks this yet.

Run PHPStan and deptrac from the project's root: the paths in both configs are from there.

PHPStan analyses only `.php` files in a directory it's given, so list each script in `bin/` without the extension by name in `phpstan.neon`'s `paths` (`bin/convert`), or the code rules and size limits never reach it. A project with other directories for adapters or entry points sets, in its `phpstan.neon`, each list in `config/phpstan.neon` that names the role's directory: one for each impure operation, from `phpIanfStrictBaselineFilesystemPaths` to `phpIanfStrictBaselineConfigurationPaths`, `phpIanfStrictBaselineCorePaths` and `phpIanfStrictBaselineSizeLimitedPaths`.

## What it doesn't check yet

[The rulebook's coverage table](https://github.com/ianflanagan1/code-rulebook/blob/main/rulebook/setups/ianf-strict/COVERAGE.md) has the full list. The main gaps:

- Roles come from directories, not from each declaration's own code, and decision code isn't told from value code.
- Libraries: deptrac leaves out classes in no layer, so orchestrators, adapters and entrypoints can use any library, including one that does I/O. The core's use of one is followed into its code, which finds only the impure operations the bans list, not state kept in an instance's fields, and reports every call through a trait's method or a template method as one it can't resolve.
- The bans list the common functions for each impure operation, not every one, and the clock and randomness are allowed in every adapter and entrypoint until PHP's shared code holds the system adapters.
- Only a checked exception that escapes undeclared is reported: not one a `@throws` tag declares, and not a `RuntimeException` thrown for an expected failure.
- The rules go-ianf-strict-baseline's analyzers enforce: the core takes values rather than ports, and orchestrators branch only on what the core returns.
- A family without a `@phpstan-sealed` tag isn't known to be closed, and only functions marked `#[\NoDiscard]` have their results checked.
- `verify` checks only `.gitignore` so far. It doesn't yet check that a project includes these configs, runs Pint and `verify` in its `check` and doesn't weaken them, or that `phpstan.neon` lists every script in `bin/` without an extension.

## The code rulebook

`src/Core/RulebookListing.php` records which checks enforce each of the rulebook's clauses and the tests that prove it. `rulebook-coverage.json` holds that listing. A test fails when the file is out of date or names a test that doesn't exist. The `rulebook` task, which `check` runs, checks the listing with `check-listing` from the code-rulebook release `RULEBOOK_RELEASE` pins: it fails on a clause that applies to PHP and isn't assessed, a role that isn't mapped, or a claim of full or partial coverage without proof. It runs through Go, which devbox pins for that alone.

code-rulebook is a private repository, so the task needs Git access to it. Locally, have Git fetch it over SSH: `git config --global url."git@github.com:ianflanagan1/code-rulebook".insteadOf "https://github.com/ianflanagan1/code-rulebook"`. In CI, give the job a token that can read it.

[docs/phpstan-rules-to-build.md](docs/phpstan-rules-to-build.md) lists the PHPStan rules still to build, each with the clauses it enforces, what it must report and allow, and its fixtures. Each is built alone, test-first, and its entry deleted when it is. A test fails when an entry is incomplete, names a clause the listing lacks or covers in full, or names a rule that exists.

`tests/ConformanceTest.php` is the proof. It runs deptrac and PHPStan, with the shipped configs, over `tests/fixtures/conformance`, which has a file for each violation the rulebook describes. Each test is named for its clause. A few tests, named `…_misses_…`, each pin down a gap the listing records, such as that only `#[\NoDiscard]` functions are checked, so closing the gap fails the test until the listing changes too.

When a check changes what it enforces, or the baseline moves to a new rulebook release:

1. Bump `RULEBOOK_RELEASE` in `Taskfile.yml`, if moving.
2. Assess each clause the `rulebook` task reports. Where an existing check reaches, add the violation to `tests/fixtures/conformance` and a test named for its clause. Where catching it needs a check the baseline doesn't have, record the coverage the existing checks give and write down what the missing check must do; it is built separately. A missing PHPStan rule gets an entry in [docs/phpstan-rules-to-build.md](docs/phpstan-rules-to-build.md).
3. Update the claim in `src/Core/RulebookListing.php`.
4. `devbox run -- task rulebook-coverage`, then `check`.

## Development

PHP and Composer come from the project's devbox, so neither needs installing.

```sh
devbox run -- task setup
devbox run -- task check   # composer.json, php-ianf-strict-baseline verify, the tests (including the conformance tests), PHPStan, deptrac, Pint and the rulebook listing
```

The package follows the rules it ships: `src/Core/` is pure, `src/Adapters/Driving/PhpStan/` holds the PHPStan rules, `src/Orchestrators/Verify.php` reads the project through the `src/Ports/ProjectFiles.php` port for `verify`, and `bin/php-ianf-strict-baseline` is the entrypoint. Its own `pint.json` extends `config/pint.json`, as a project's does.
