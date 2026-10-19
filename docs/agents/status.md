# Project status

_Every PR updates this file. Keep it short: it's the hand-off note for the next agent._

Last updated: 2026-10-18 by `docs/block-theme-pivot`

## What exists
- The repo is bootstrapped (`README.md`, `.gitignore`, `.editorconfig`), and `main` is the default branch.
- Agent infrastructure is in place:
  - `CLAUDE.md` and `docs/agents/*` (docs and ADRs 0001–0008)
  - `.claude/agents` (5 subagents; Opus for architect and adversarial-reviewer, Sonnet for security, accessibility and test-engineer) and `.claude/skills` (ship-pr, review, new-block, wp-local)
  - `.claude/hooks`: prompt capture, git guard, PHP lint
  - `.claude/settings.json` (narrow allow list)
  - the PR template and `.agents/prompts/`
- **Direction (ADR 0008):** Blammo is a full block (FSE) theme: `theme.json` tokens, `render.php` blocks, no Sprig/Twig. wp-styleguide stays as an optional dev plugin. ADRs 0001, 0003 and 0007 are superseded.
- GitHub ruleset "Protect main" is active: PRs required (0 approvals), no force pushes, no deletion, no bypass actors.

## In flight / next up (in order)
1. `feat/theme-scaffold`: build the block theme per `architecture.md` and ADR 0008, porting only the useful RH_ → Blammo_ classes. Requirements:
   - a `style.css` header (Requires at least: 6.8), and `theme.json` v3 with palette, typography (self-hosted `fontFace`), spacing, layout and `templateParts` (header, footer); core default palettes and free-form values off
   - `templates/` (at least index, single, page, archive, search, 404; one `<main>` each), `parts/header.html` and `parts/footer.html`, and patterns for all template text (`Slug: blammo/*`, category `blammo`)
   - `Blammo_Blocks`: manifest registration (no fatal if `build/` is missing), plus the block and pattern categories
   - `Blammo_Scripts_And_Styles`: handles from `build/*.asset.php`, `add_editor_style` for canvas CSS (in `action_after_setup_theme`), editor JS on `enqueue_block_editor_assets` only
   - a webpack build with `--webpack-copy-php --blocks-manifest` (verify both flags exist)
   - `Blammo_Styleguide`: the `styleguide_path` `request` guard with PHPUnit for `../`, `%2e%2e/` and `/etc/passwd`, plus the `page()` / `block()` shell helpers
   - no Sprig, Twig, mu-plugins or `bin/install-mu-plugins`
   - all tool configs exclude `.claude/worktrees/`
2. `chore/testing-ci`: wp-env, PHPUnit, Jest, Playwright + axe, GitHub Actions `ci.yml` and `deploy.yml`.
   - Deploy ships `build/` (including `render.php` files and `blocks-manifest.php`), `theme.json`, `templates/`, `parts/`, `patterns/`, `styles/`, `assets/` and the PHP. `src/` and `styleguide/` are dev-only.
   - CI runs `npm run build` before PHPUnit and e2e.
   - `.wp-env.json` installs wp-styleguide from a pinned commit zip and sets `WP_DEVELOPMENT_MODE` to `'theme'`.
   - `deploy.yml` stamps `style.css` `Version` on every build, because the theme pattern cache is keyed to it.
3. `feat/mcp-adapter`:
   - `bin/wp-local` (refuses destructive subcommands and the `--exec`/`--require` global flags unless `WP_LOCAL_ALLOW_DESTRUCTIVE=1`, and limits `db export` to `~/blammo-backups/`), `.env.local.example`, `.mcp.json`
   - `Blammo_Abilities` + tests (safety rules in local-environment.md)
   - local site setup: the mcp-adapter plugin, a `claude` user with a random password, theme activation
4. GitHub issues for deferred starter classes: CDN, Slack, Share_Tools, Redirects, Breadcrumbs, Post_Type_Archives

## Known gaps
- There's no theme code yet, so the build, lint and test commands in `CLAUDE.md` don't work yet.
- `bin/wp-local`, `.env.local.example` and `.mcp.json` don't exist yet. Don't modify blammo.local until `feat/mcp-adapter` is merged.
- `.claude/hooks/lint-php.sh` falls back to `php -l` only until `vendor/bin/phpcs` exists.
- **wp-styleguide upstream:** path-traversal, redirect, rewrite-anchor and title-injection fixes are in [kingkool68/wp-styleguide#1](https://github.com/kingkool68/wp-styleguide/pull/1), not merged yet. Blammo keeps its own `styleguide_path` guard permanently.
- Activating Blammo on blammo.local replaces classic menus, widgets and Customizer settings. Site-editor customizations are stored in the DB and override theme files.
- `.claude/settings.json` still allows `bin/install-mu-plugins`, a script that's no longer planned. The rule is harmless; remove it when the permission list is next revised.
