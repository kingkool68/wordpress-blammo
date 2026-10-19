# Project status

_Every PR updates this file. Keep it short: it's the hand-off note for the next agent._

Last updated: 2026-10-18 by `feat/mcp-adapter`

## What exists
- The repo is bootstrapped (`README.md`, `.gitignore`, `.editorconfig`), and `main` is the default branch.
- Agent infrastructure is in place:
  - `CLAUDE.md` and `docs/agents/*` (docs and ADRs 0001–0008)
  - `.claude/agents` (5 subagents; Opus for architect and adversarial-reviewer, Sonnet for security, accessibility and test-engineer) and `.claude/skills` (ship-pr, review, new-block, wp-local)
  - `.claude/hooks`: prompt capture, git guard, PHP lint
  - `.claude/settings.json` (narrow allow list)
  - the PR template and `.agents/prompts/`
- **Direction (ADR 0008):** Blammo is a full block (FSE) theme: `theme.json` tokens, `render.php` blocks, no Sprig/Twig. wp-styleguide stays as an optional dev plugin. ADRs 0001, 0003 and 0007 are superseded.
- Local site tooling (`feat/mcp-adapter`, pulled ahead of the scaffold at the user's request):
  - `bin/wp-local` with its guard and `tests/bin/test-wp-local.sh`, `.env.local.example`, `.mcp.json` (`blammo-wp`)
  - on blammo.local: the `mcp-adapter` plugin 0.7.1 is active; backups live in `~/blammo-backups/`
- GitHub ruleset "Protect main" is active: PRs required (0 approvals), no force pushes, no deletion, no bypass actors.

## In flight / next up (in order)
1. `feat/theme-scaffold`: build the block theme per `architecture.md` and ADR 0008, porting only the useful RH_ → Blammo_ classes. Requirements:
   - a `style.css` header (Requires at least: 7.0: the documented skip-link behaviour is server-side only from 7.0, and blammo.local runs 7.1.3), and `theme.json` v3 with palette, typography (self-hosted `fontFace`), spacing, layout and `templateParts` (header, footer); core default palettes and free-form values off
   - `templates/` (at least index, single, page, archive, search, 404; one `<main>` each), `parts/header.html` and `parts/footer.html`, and patterns for all template text (`Slug: blammo/*`, category `blammo`)
   - `Blammo_Blocks`: manifest registration (no fatal if `build/` is missing), plus the block and pattern categories
   - `Blammo_Scripts_And_Styles`: handles from `build/*.asset.php`, `add_editor_style` for canvas CSS (in `action_after_setup_theme`), editor JS on `enqueue_block_editor_assets` only
   - a webpack build with `--webpack-copy-php --blocks-manifest` (verify both flags exist)
   - `Blammo_Styleguide`: the `styleguide_path` `request` guard with PHPUnit for `../`, `%2e%2e/` and `/etc/passwd`, plus the `page()` / `block()` shell helpers (`.wp-site-blocks` wrapper, constrained `<main>`, `document_title_parts` filter)
   - no Sprig, Twig, mu-plugins or `bin/install-mu-plugins`
   - `phpcs.xml.dist` excludes `render.php`, `patterns/` and `styleguide/` from PrefixAllGlobals
   - all tool configs exclude `.claude/worktrees/`
2. `chore/testing-ci`: wp-env, PHPUnit, Jest, Playwright + axe, GitHub Actions `ci.yml` and `deploy.yml`.
   - Deploy ships `build/` (including `render.php` files and `blocks-manifest.php`), `theme.json`, `templates/`, `parts/`, `patterns/`, `styles/`, `assets/` and the PHP. `src/` and `styleguide/` are dev-only.
   - CI runs `npm run build` before PHPUnit and e2e.
   - `.wp-env.json` installs wp-styleguide from a pinned commit zip and sets `WP_DEVELOPMENT_MODE` to `'theme'`. Pin wp-env `core` to 7.0 or later.
   - `deploy.yml` stamps `style.css` `Version` on every build, because the theme pattern cache is keyed to it.
   - CI runs `tests/bin/*.sh` (ideally as `npm run test:bin`).
3. Local site setup (human-approved, each step after a `bin/wp-local db export`; no PR):
   - create the `claude` administrator with a random password nobody keeps and no application passwords (the `blammo-wp` MCP server can't start without it)
   - set `WP_DEVELOPMENT_MODE` to `'theme'` in blammo.local's `wp-config.php`
   - after `feat/theme-scaffold` merges: activate Blammo (see the activation gap below)
4. `feat/abilities` (after `chore/testing-ci`, which it needs for PHPUnit): `Blammo_Abilities` + tests (safety rules in local-environment.md).
5. GitHub issues for deferred starter classes: CDN, Slack, Share_Tools, Redirects, Breadcrumbs, Post_Type_Archives

## Known gaps
- There's no theme code yet, so the build, lint and test commands in `CLAUDE.md` don't work yet.
- No theme abilities yet, so MCP only exposes core and plugin abilities.
- `bin/wp-local`'s guard is defence in depth. Edits to `bin/wp-local` and `.env.local` aren't behind `ask` in `.claude/settings.json` yet, so an agent could edit the guard in a worktree and then run a pre-approved `bin/wp-local` prefix. Adding those `ask` rules is the human's call.
- `.claude/hooks/lint-php.sh` falls back to `php -l` only until `vendor/bin/phpcs` exists.
- **wp-styleguide upstream:** the path-traversal, redirect, rewrite-anchor and title-injection fixes in [kingkool68/wp-styleguide#1](https://github.com/kingkool68/wp-styleguide/pull/1) merged 2026-10-19 (`1664c7e`). Pin wp-styleguide to that commit or later. Blammo keeps its own `styleguide_path` guard permanently.
- Activating Blammo on blammo.local replaces classic menus, widgets and Customizer settings. Site-editor customizations are stored in the DB and override theme files.
- `.claude/settings.json` still allows `bin/install-mu-plugins`, a script that's no longer planned. The rule is harmless; remove it when the permission list is next revised.
