# php-ianf-strict-baseline

See README.md. `devbox run -- task check` runs everything, and must pass before finishing a change.

- PHP and Composer come from the project's devbox. Run one-off commands as `devbox run -- php …` or `devbox run -- composer …` rather than installing PHP.
- After changing `config/`, `src/Adapters/Driving/PhpStan/`, `tests/fixtures/conformance` or `src/Core/RulebookListing.php`, run `devbox run -- task rulebook-coverage`, then `check`.
- `tests/fixtures/conformance` breaks the rules on purpose. PHPStan and Pint leave it out.
- `docs/phpstan-rules-to-build.md` lists every PHPStan rule the baseline still needs. When a clause needs a rule the baseline doesn't have, or a requirement for a planned rule arrives or changes, add or change its entry in the same change, in the form the file gives, and don't build the rule in that change. Build one rule to a session, test-first, and delete its entry in the change that adds it. `tests/RulesToBuildTest.php` fails on an entry that is incomplete, that names a clause the listing lacks or covers in full, or that names a rule that exists.
