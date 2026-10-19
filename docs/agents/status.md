# Project status

_Every PR updates this file. Keep it short: it's the hand-off note for the next agent._

Last updated: 2026-10-18 by `feat/theme-scaffold`

## What exists
- Agent infrastructure: `CLAUDE.md`, `docs/agents/*` (ADRs 0001–0008), `.claude/agents` (5 subagents; Opus for architect and adversarial-reviewer, Sonnet for security, accessibility and test-engineer), `.claude/skills` (ship-pr, review, new-block, wp-local), `.claude/hooks`, `.claude/settings.json`, the PR template and `.agents/prompts/`.
- **Direction (ADR 0008):** a full block (FSE) theme: `theme.json` tokens, `render.php` blocks, no Sprig/Twig. wp-styleguide is an optional dev plugin. ADRs 0001, 0003 and 0007 are superseded.
- Local site tooling (`feat/mcp-adapter`):
  - `bin/wp-local` with its guard and `tests/bin/test-wp-local.sh`, `.env.local.example`, `.mcp.json` (`blammo-wp`)
  - on blammo.local: the `mcp-adapter` plugin 0.7.1 is active; backups live in `~/blammo-backups/`
- **The theme scaffold** (`feat/theme-scaffold`):
  - `style.css` (Requires at least 7.0, PHP 8.1) and `theme.json` v3: a 7-colour role palette (all text pairs ≥ 6.2:1), self-hosted Inter (6 woff2 faces, OFL licence included), fluid font sizes, spacing `10`–`80`, layout 45rem / 75rem, `custom.lineHeight`, `templateParts` header/footer. Core default palettes, gradients, duotone, font sizes and spacing sizes, and free-form colour/size/spacing values are off.
  - `templates/` index, home, single, page, archive, search, 404 (one `<main>` each); `parts/` header (site title + navigation), footer (→ `blammo/footer` pattern).
  - `patterns/`: footer, query-loop, home-heading, search-form (attrs via `serialize_block()`), 404. All template text lives in them.
  - `functions/`: `Blammo_Blocks` (manifest registration, no fatal without `build/`; block + pattern category `blammo`), `Blammo_Scripts_And_Styles`, `Blammo_Styleguide` (`styleguide_path` guard, `document_title_parts`, `block()` / `page()`), `Blammo_SVG` (ported; icon names restricted to `[a-z0-9_-]`, reads only `.svg` files inside `assets/icons/`, decorative `aria-hidden` by default, `label` → `role="img"`), `Blammo_Staging` (ported).
  - `src/` (SCSS base: `rem()`/`em()`, `visually-hidden`, breakpoints; `blammo` + `editor` entries; empty `src/blocks/`), `assets/icons/` (6 icons), `styleguide/` (index, colors, typography, icons).
  - Tooling: wp-scripts 36 (`--webpack-copy-php --blocks-manifest`, both verified with a probe block), ESLint flat config, Stylelint, Prettier, PHPCS (WPCS 3 + PHPCompatibilityWP, Yoda conditions banned), PHPUnit 9.6 unit suite (53 tests). `npm run test:php` runs the unit suite for now.
- GitHub ruleset "Protect main" is active: PRs required (0 approvals), no force pushes, no deletion, no bypass actors.

## In flight / next up (in order)
1. `chore/testing-ci`: wp-env, PHPUnit integration, Jest, Playwright + axe, GitHub Actions `ci.yml` and `deploy.yml`.
   - Integration tests owed by the scaffold: hook registration and behaviour of `Blammo_Blocks` (category, pattern category, missing-manifest early return), `Blammo_Scripts_And_Styles` (handles, `add_editor_style`, empty-JS skip), `Blammo_Styleguide::page()` / `block()` and `filter_document_title_parts()`, `Blammo_Staging`; every `blammo/*` pattern registered; e2e block validity for every template and part plus an axe scan of each template.
   - Extend `npm run test:php` to run unit + integration.
   - Deploy ships `build/` (including `render.php` files and `blocks-manifest.php`), `theme.json`, `templates/`, `parts/`, `patterns/`, `styles/`, `assets/` and the PHP. `src/` and `styleguide/` are dev-only.
   - CI runs `npm run build` before PHPUnit and e2e.
   - `.wp-env.json` installs wp-styleguide from a pinned commit zip (`1664c7e` or later) and sets `WP_DEVELOPMENT_MODE` to `'theme'`. Pin wp-env `core` to 7.0 or later.
   - `deploy.yml` stamps `style.css` `Version` on every build, because the theme pattern cache is keyed to it.
   - CI runs `tests/bin/*.sh` (ideally as `npm run test:bin`).
   - e2e global setup publishes a links-based `wp_navigation` post (see Known gaps), or every axe scan fails on core's Page List fallback.
2. Local site setup (human-approved, each step after a `bin/wp-local db export`; no PR):
   - create the `claude` administrator with a random password nobody keeps and no application passwords (the `blammo-wp` MCP server can't start without it)
   - set `WP_DEVELOPMENT_MODE` to `'theme'` in blammo.local's `wp-config.php`
   - after `feat/theme-scaffold` merges: `npm run build` in the main checkout, then activate Blammo (export the DB first: the first page view creates a `wp_navigation` post) and publish a links-based `wp_navigation` menu (see Known gaps)
3. `feat/abilities` (after `chore/testing-ci`, which it needs for PHPUnit): `Blammo_Abilities` + tests (safety rules in local-environment.md).
4. GitHub issues for deferred starter classes: CDN, Slack, Share_Tools, Redirects, Breadcrumbs, Post_Type_Archives

## Known gaps
- `npm run env:*`, `npm run test:js` and `npm run test:e2e` don't exist until `chore/testing-ci`.
- No theme abilities yet, so MCP only exposes core and plugin abilities.
- **Navigation fallback:** `parts/header.html` uses a ref-less `wp:navigation`. On the first front-end render, core auto-creates a `wp_navigation` post (a classic menu converted, or `<!-- wp:page-list /-->`). That **writes to the DB on a GET**, so on blammo.local export the DB before the first page view after activation. Page List inside Navigation renders `ul > ul` (axe `list`, serious). It goes away once the most recently published `wp_navigation` post contains link blocks, which was verified with axe. `chore/testing-ci` must create such a menu in e2e global setup, after any auto-created one.
- The posts page heading is always "Latest posts" (`blammo/home-heading`), even when Settings → Reading names a posts page. Change it in the site editor, or make it a block later.
- The scaffold was verified in a throwaway SQLite WordPress 7.1.3 (not blammo.local): every template and the styleguide render with no PHP notices, all templates and parts load in the site editor with 0 invalid blocks, axe is clean with a menu present (including paginated archives), and the `styleguide_path` traversal attempts fall back to the index. Those checks were one-off scripts; `chore/testing-ci` makes them permanent.
- `bin/wp-local`'s guard is defence in depth. Edits to `bin/wp-local` and `.env.local` aren't behind `ask` in `.claude/settings.json` yet, so an agent could edit the guard in a worktree and then run a pre-approved `bin/wp-local` prefix. Adding those `ask` rules is the human's call.
- `.claude/hooks/lint-php.sh` uses `vendor/bin/phpcs` once `composer install` has run in that checkout; otherwise it falls back to `php -l`.
- **wp-styleguide upstream:** the path-traversal, redirect, rewrite-anchor and title-injection fixes in [kingkool68/wp-styleguide#1](https://github.com/kingkool68/wp-styleguide/pull/1) merged 2026-10-19 (`1664c7e`). Pin wp-styleguide to that commit or later. Blammo keeps its own `styleguide_path` guard permanently.
- Activating Blammo on blammo.local replaces classic menus, widgets and Customizer settings. Site-editor customizations are stored in the DB and override theme files.
- `.claude/settings.json` still allows `bin/install-mu-plugins`, a script that's no longer planned. The rule is harmless; remove it when the permission list is next revised.
