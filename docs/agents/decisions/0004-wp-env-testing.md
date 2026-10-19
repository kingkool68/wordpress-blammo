# 0004 — All automated tests run on `@wordpress/env`

- Status: accepted (2026-10-18)

## Context
The local site (Local by Flywheel) holds real content and is tied to one machine. CI needs an identical, disposable WordPress.

## Decision
PHPUnit (via `wp-env run tests-cli`), Jest and Playwright all run against wp-env locally and in GitHub Actions. Tests never touch blammo.local. Sprig is mounted into wp-env as an mu-plugin.

## Consequences
- Running tests requires Docker.
- Parallel worktrees must use distinct `WP_ENV_PORT` / `WP_ENV_TESTS_PORT`.
- Worktree branches can be checked visually in wp-env without switching the main checkout.

## Alternatives considered
- **Tests against a separate DB on the Local site:** rejected; it's faster locally but means two configs and no CI parity.
- **Pure unit tests with Brain Monkey only:** rejected; render and registration behaviour need real WordPress.
