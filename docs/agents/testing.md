# Testing

> Status: the infrastructure lands in `chore/testing-ci`. Until then, these are the target rules.

All automated tests run against **`@wordpress/env`** (Docker), locally and in CI. Tests never touch blammo.local.

Blocks register from `build/`, so run `npm run build` before PHP and e2e tests (CI does this first).

| Type | Location | Runner | Command |
|---|---|---|---|
| PHP unit (pure helpers) | `tests/phpunit/unit/` | PHPUnit | `npm run test:php` |
| PHP integration (WP loaded) | `tests/phpunit/integration/` | PHPUnit + `WP_UnitTestCase` in wp-env `tests-cli` | `npm run test:php` |
| JS unit | `tests/js/` or `*.test.js` beside source | Jest via `wp-scripts test-unit-js` | `npm run test:js` |
| E2E + a11y | `tests/e2e/*.spec.js` | Playwright via `wp-scripts test-playwright`, with `@wordpress/e2e-test-utils-playwright` and `@axe-core/playwright` | `npm run test:e2e` |

## What each PR must include
- **New PHP class or method:** integration tests for its hook registration and its behaviour.
- **Anything that renders** (`render.php`, patterns, styleguide shell): a test that hostile input (`<script>`, `"onmouseover=`, `javascript:` URLs) comes out escaped. Render blocks with `render_block()` / `do_blocks()`.
- **New block:** registration (PHP), two instances on one page render without a fatal (PHP), editor insert plus front-end render (e2e), and an axe scan.
- **New pattern:** it's registered (`WP_Block_Patterns_Registry::get_instance()->is_registered( 'blammo/<name>' )`) and its markup parses without invalid blocks.
- **New or changed template, part, `theme.json` styles or visible UI:** an e2e smoke test plus an axe scan (no `serious`/`critical` violations) on a page that uses it.
- **`styleguide_path` guard:** PHPUnit for `../`, `%2e%2e/` and `/etc/passwd` (permanent).
- **Bug fix:** a test that fails before the fix.

## Conventions
- Test class names are `Test_Blammo_<Thing>`, in files named `test-blammo-<thing>.php`.
- Use factories (`self::factory()->post->create()`) and never rely on existing content.
- E2E specs use the `requestUtils` REST helpers for setup, not UI clicks.
- Every test runner config must ignore `.claude/worktrees/` (see workflow.md).
- In worktrees, set `WP_ENV_PORT` / `WP_ENV_TESTS_PORT` to unique ports so parallel agents don't collide. See [local-environment.md](local-environment.md).
