# Testing

> Status: the PHP **unit** suite exists (`composer test:unit`; `npm run test:php` runs it until wp-env lands). wp-env, integration tests, Jest, Playwright and CI land in `chore/testing-ci`.

All automated tests run against **`@wordpress/env`** (Docker), locally and in CI. Tests never touch blammo.local.

Blocks register from `build/`, so run `npm run build` before PHP and e2e tests (CI does this first).

| Type | Location | Runner | Command |
|---|---|---|---|
| PHP unit (pure helpers) | `tests/phpunit/unit/` | PHPUnit 9.6, no WordPress: `bootstrap.php` stubs the few WP functions used and records `add_action`/`add_filter` calls in `$GLOBALS['blammo_test_hooks']` | `composer test:unit` (and `npm run test:php`) |
| PHP integration (WP loaded) | `tests/phpunit/integration/` | PHPUnit + `WP_UnitTestCase` in wp-env `tests-cli` | `npm run test:php` |
| JS unit | `tests/js/` or `*.test.js` beside source | Jest via `wp-scripts test-unit-js` | `npm run test:js` |
| E2E + a11y | `tests/e2e/*.spec.js` | Playwright via `wp-scripts test-playwright`, with `@wordpress/e2e-test-utils-playwright` and `@axe-core/playwright` | `npm run test:e2e` |
| `bin/` scripts | `tests/bin/test-<script>.sh` | plain bash, no Docker or Local needed | `tests/bin/test-wp-local.sh` |

## What each PR must include
- **New PHP class or method:** integration tests for its hook registration and its behaviour.
- **Anything that renders** (`render.php`, patterns, styleguide shell): a test that hostile input (`<script>`, `"onmouseover=`, `javascript:` URLs) comes out escaped. Render blocks with `render_block()` / `do_blocks()`.
- **New block:** registration (PHP), two instances on one page render without a fatal (PHP), editor insert plus front-end render (e2e), and an axe scan.
- **New pattern:** it's registered (`WP_Block_Patterns_Registry::get_instance()->is_registered( 'blammo/<name>' )`). Block validity is client-side only, so PHPUnit can't check it: an e2e test inserts the pattern (or opens the template in the site editor) and asserts no block in `core/block-editor` `getBlocks()` has `isValid === false`.
- **New or changed template, part, `theme.json` styles or visible UI:** an e2e smoke test plus an axe scan (no `serious`/`critical` violations) on a page that uses it.
- **`styleguide_path` guard:** PHPUnit for `../`, `%2e%2e/` and `/etc/passwd` (permanent).
- **Changed `bin/` script:** a case in `tests/bin/test-<script>.sh`, including one that fails without the change.
- **Bug fix:** a test that fails before the fix.

## Conventions
- Test class names are `Test_Blammo_<Thing>`, in files named `test-blammo-<thing>.php`. PHPCS covers `tests/` but doesn't require doc comments on test classes and methods.
- Use factories (`self::factory()->post->create()`) and never rely on existing content.
- E2E specs use the `requestUtils` REST helpers for setup, not UI clicks.
- Every test runner config must ignore `.claude/worktrees/` (see workflow.md).
- In worktrees, set `WP_ENV_PORT` / `WP_ENV_TESTS_PORT` to unique ports so parallel agents don't collide. See [local-environment.md](local-environment.md).
