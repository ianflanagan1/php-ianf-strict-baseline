# php-baseline

The checks every PHP project of mine shares: the architecture (a functional core, and a shell of orchestrators, driving adapters and driven adapters behind ports), the shape of its code, and its formatting. It is the PHP counterpart of [go-baseline](https://github.com/ianflanagan1/go-baseline) and [@ianflanagan1/ts-baseline](https://github.com/ianflanagan1/ts-baseline), and enforces the [code rulebook](https://github.com/ianflanagan1/code-rulebook) in PHP's terms.

| Export | What it is |
|---|---|
| `config/deptrac.yaml` | deptrac layers for the roles, by directory, and which may depend on which |
| `config/phpstan.neon` | spaze/phpstan-disallowed-calls bans on each effect, outside the roles allowed to perform it, and on dependency-injection containers; the baseline's own PHPStan rules, in `src/Adapters/Driving/PhpStan/`; and level 4, the lowest at which PHPStan reports a match that leaves out a case |
| `config/pint.json` | The Pint settings, for a project's `pint.json` to extend |
| `php-baseline rulebook` | Prints how the baseline enforces each clause of the rulebook, as the coverage listing the rulebook checks |

## Roles

A file's directory decides its role:

| Role | Directory | May depend on | May perform effects |
|---|---|---|---|
| Core | `src/Core/` | The core | None |
| Port | `src/Ports/` | The core | None: ports are interfaces |
| Orchestrator | `src/Orchestrators/` | The core, ports and other orchestrators | None: only through ports |
| Driving adapter | `src/Adapters/Driving/` | The core, orchestrators and other driving adapters | All but reading configuration |
| Driven adapter | `src/Adapters/Driven/` | The core, ports and other driven adapters | All but reading configuration |
| Entrypoint | `bin/` and `public/` | Anything | All |

Tests may do anything. A class under `src/` in none of these directories has no role: deptrac puts it in an Unplaced layer, which may depend on nothing and which nothing may depend on, and PHPStan reports the effects it performs. A stray class that neither depends on nor is used by a class in a role isn't caught yet.

A driving adapter, such as a controller or a console command, calls an orchestrator directly; it imports no port and no driven adapter. An entrypoint constructs the adapters and orchestrators and starts a driving adapter.

A dependency of mine, in `IanFlanagan\<Project>\`, takes its roles from its namespace: `\Core\`, `\Ports\`, `\Orchestrators\`, `\Adapters\Driving\` and `\Adapters\Driven\` give those roles, and anything else but `\Tests\` is Unplaced. The root-namespace rule maps a project's namespace to `src/`, so the namespace and the directory give a class the same role. A project's core can then use a library's core, and its driven adapters implement a library's ports, while its core calling a library's orchestrators is reported. Other vendors' classes are in no layer.

A port is an interface in `src/Ports/`, named for what the domain needs from it (`Orders`, `Clock`), not for the vendor or technology behind it (`MysqlOrders`, `RedisCache`). Nothing checks the naming. Ports have their own directory, rather than living beside the orchestrator that calls them as in Go, because a PHP driven adapter has to name the interface it implements, and driven adapters may not import orchestrators.

The effects are the rulebook's: the clock, randomness, the filesystem, the network (including databases and the request superglobals), processes (including `exit`), the console (including `echo`), global logging, configuration (`getenv()`, `$_ENV`, `$_SERVER`) and global state (`global`, `$GLOBALS`). `new DateTimeImmutable()` reads the clock, so the core parses dates with `DateTimeImmutable::createFromFormat()` and takes the current time as a value.

## Code rules

The baseline's PHPStan rules apply to every role:

- **Closed families.** Seal a family of variants with a `@phpstan-sealed A|B` tag on its interface or abstract class, so PHPStan reports a class that joins it unlisted. Branch on it with `match (true) { $x instanceof A => …, $x instanceof B => … }`, and on an enum with `match ($x)`, so PHPStan reports a match that leaves a case out. A `switch` on either, or a `default` arm in a match on either, is reported: name each case, listing those that share a branch in one arm.
- **Composition.** A class extends only a class `phpBaselineAllowedParentClasses` names (PHPUnit's `TestCase` by default) or one of PHP's own exceptions. Share behaviour by taking a collaborator instead.
- **Parameters.** No parameter has a default value, except in a method that overrides or implements one whose signature has it.
- **Results.** Mark a function that returns an expected failure as a value `#[\NoDiscard]`, so PHPStan reports a call that drops it.
- **Static state.** No static property or static variable outside adapters, the entrypoints and tests.
- **Size.** Each function in an adapter or an entrypoint, and a script's top-level code there, is at most 50 lines. A project with other directories for them sets `phpBaselineSizeLimitedPaths`.
- **Cycles.** No class depends on itself through others.
- **Wiring.** The entrypoint constructs each adapter and orchestrator and passes it in. The common containers (PSR-11, PHP-DI, Symfony's, Laravel's and the rest) are banned.

## Use

```sh
composer require --dev ianflanagan1/php-baseline phpstan/extension-installer
```

phpstan/extension-installer includes `config/phpstan.neon` and spaze/phpstan-disallowed-calls. Without it, include both in `phpstan.neon`:

```neon
includes:
    - vendor/spaze/phpstan-disallowed-calls/extension.neon
    - vendor/ianflanagan1/php-baseline/config/phpstan.neon
```

`deptrac.yaml`:

```yaml
imports:
  - vendor/ianflanagan1/php-baseline/config/deptrac.yaml

deptrac:
  paths:
    - src
    - bin
```

`pint.json`, which sets no rules of its own:

```json
{
    "extend": "vendor/ianflanagan1/php-baseline/config/pint.json"
}
```

The project's `check` task runs `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` and `vendor/bin/deptrac analyse`, and fails if any of them does. Nothing checks this yet.

Run PHPStan and deptrac from the project's root: the paths in both configs are from there.

PHPStan analyses only `.php` files in a directory it's given, so list each script in `bin/` without the extension by name in `phpstan.neon`'s `paths` (`bin/convert`), or the code rules and size limits never reach it. A project with other directories for adapters or entry points sets `phpBaselineEffectPaths` and `phpBaselineEntrypointPaths` in its `phpstan.neon`.

## What it doesn't check yet

[The rulebook's coverage table](https://github.com/ianflanagan1/code-rulebook/blob/main/COVERAGE.md) has the full list. The main gaps:

- Libraries: deptrac leaves out classes in no layer, so the core and orchestrators can use any library, including one that does I/O.
- The bans list the common functions for each effect, not every one.
- The rules go-baseline's analyzers enforce: the core takes values rather than ports, and orchestrators branch only on what the core returns.
- A family without a `@phpstan-sealed` tag isn't known to be closed, and only functions marked `#[\NoDiscard]` have their results checked.
- There is no `verify` yet, to check that a project includes these configs, runs Pint in its `check` and doesn't weaken them, or that `phpstan.neon` lists every script in `bin/` without an extension.

## The code rulebook

`src/Core/RulebookListing.php` records which checks enforce each of the rulebook's clauses and the tests that prove it. `rulebook-coverage.json` holds that listing. A test fails when the file is out of date or names a test that doesn't exist. The `rulebook` task, which `check` runs, checks the listing with `check-listing` from the code-rulebook release `RULEBOOK_RELEASE` pins: it fails on a clause that applies to PHP and isn't assessed, a role that isn't mapped, or a claim of full or partial coverage without proof. It runs through Go, which devbox pins for that alone.

code-rulebook is a private repository, so the task needs Git access to it. Locally, have Git fetch it over SSH: `git config --global url."git@github.com:ianflanagan1/code-rulebook".insteadOf "https://github.com/ianflanagan1/code-rulebook"`. In CI, give the job a token that can read it.

`tests/ConformanceTest.php` is the proof. It runs deptrac and PHPStan, with the shipped configs, over `tests/fixtures/conformance`, which has a file for each violation the rulebook describes. Each test is named for its clause. One test pins down a gap the listing records, that only `#[\NoDiscard]` functions are checked, so closing the gap fails it until the listing changes too.

When a check changes what it enforces, or the baseline moves to a new rulebook release:

1. Bump `RULEBOOK_RELEASE` in `Taskfile.yml`, if moving.
2. Assess each clause the `rulebook` task reports. Where an existing check reaches, add the violation to `tests/fixtures/conformance` and a test named for its clause. Where catching it needs a check the baseline doesn't have, such as a new PHPStan rule, record the coverage the existing checks give and write down what the missing check must do; it is built separately.
3. Update the claim in `src/Core/RulebookListing.php`.
4. `devbox run -- task rulebook-coverage`, then `check`.

## Development

PHP and Composer come from the project's devbox, so neither needs installing.

```sh
devbox run -- task setup
devbox run -- task check   # composer.json, the tests (including the conformance tests), PHPStan, deptrac, Pint and the rulebook listing
```

The package follows the rules it ships: `src/Core/` is pure, `src/Adapters/Driving/PhpStan/` holds the PHPStan rules, and `bin/php-baseline` is the entrypoint. Its own `pint.json` extends `config/pint.json`, as a project's does.
