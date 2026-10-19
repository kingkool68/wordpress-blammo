# Testing

> Status: the infrastructure lands in `chore/testing-ci`. Until then, these are the target rules.

All automated tests run against **`@wordpress/env`** (Docker), locally and in CI. Tests never touch blammo.local.

| Type | Location | Runner | Command |
|---|---|---|---|
| PHP unit (pure helpers) | `tests/phpunit/unit/` | PHPUnit | `npm run test:php` |
| PHP integration (WP loaded) | `tests/phpunit/integration/` | PHPUnit + `WP_UnitTestCase` in wp-env `tests-cli` | `npm run test:php` |
| JS unit | `tests/js/` or `*.test.js` beside source | Jest via `wp-scripts test-unit-js` | `npm run test:js` |
| E2E + a11y | `tests/e2e/*.spec.js` | Playwright via `wp-scripts test-playwright`, with `@wordpress/e2e-test-utils-playwright` and `@axe-core/playwright` | `npm run test:e2e` |

## What each PR must include
- **New PHP class or method:** integration tests for its hook registration and its behaviour.
- **Anything that renders:** a test that hostile input (`<script>`, `"onmouseover=`, `javascript:` URLs) comes out escaped.
- **New block:** registration (PHP), editor insert plus front-end render (e2e), and an axe scan.
- **New template or visible UI change:** an e2e smoke test plus an axe scan (no `serious`/`critical` violations).
- **Bug fix:** a test that fails before the fix.

## Conventions
- Test class names are `Test_Blammo_<Thing>`, in files named `test-blammo-<thing>.php`.
- Use factories (`self::factory()->post->create()`) and never rely on existing content.
- E2E specs use the `requestUtils` REST helpers for setup, not UI clicks.
- Every test runner config must ignore `.claude/worktrees/` (see workflow.md).
- In worktrees, set `WP_ENV_PORT` / `WP_ENV_TESTS_PORT` to unique ports so parallel agents don't collide. See [local-environment.md](local-environment.md).
