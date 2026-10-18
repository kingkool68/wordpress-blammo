# Project status

_Every PR updates this file. Keep it short: it's the hand-off note for the next agent._

Last updated: 2026-10-18 by `chore/agent-infrastructure`

## What exists
- The repo is bootstrapped (`README.md`, `.gitignore`, `.editorconfig`), and `main` is the default branch.
- Agent infrastructure is in place:
  - `CLAUDE.md` and `docs/agents/*` (docs and ADRs 0001–0007)
  - `.claude/agents` (5 subagents) and `.claude/skills` (ship-pr, review, new-block, wp-local)
  - `.claude/hooks`: prompt capture, git guard, PHP lint
  - `.claude/settings.json` (narrow allow list)
  - the PR template and `.agents/prompts/`

## In flight / next up (in order)
1. `feat/theme-scaffold`: port the starter (RH_ → Blammo_), following `architecture.md`. Requirements the architect review set:
   - blocks in `src/blocks/`, with Twig named `<name>-block.twig`
   - `Blammo_Dependencies` early return plus `templates/fallback.php`
   - register the `wp_kses_post` / `sanitize_title` Twig filters
   - `add_editor_style` for canvas CSS
   - a `styleguide_path` sanitizing `request` filter
   - `bin/install-mu-plugins` (Sprig + wp-styleguide), which verifies `sprig/vendor/twig`
   - all tool configs exclude `.claude/worktrees/`
2. `chore/testing-ci`: wp-env, PHPUnit, Jest, Playwright + axe, GitHub Actions `ci.yml` and `deploy.yml`
3. `feat/mcp-adapter`:
   - `bin/wp-local` (refuses destructive subcommands unless `WP_LOCAL_ALLOW_DESTRUCTIVE=1`), `.env.local.example`, `.mcp.json`
   - `Blammo_Abilities` + tests (safety rules in local-environment.md)
   - local site setup: the mcp-adapter plugin, a `claude` user with a random password, theme activation
4. GitHub issues for deferred starter classes: CDN, Slack, Share_Tools, Redirects, Breadcrumbs, Post_Type_Archives

## Known gaps
- There's no theme code yet, so the build, lint and test commands in `CLAUDE.md` don't work yet.
- `bin/wp-local`, `.env.local.example` and `.mcp.json` don't exist yet. Don't modify blammo.local until `feat/mcp-adapter` is merged.
- `.claude/hooks/lint-php.sh` falls back to `php -l` only until `vendor/bin/phpcs` exists.
- **GitHub branch protection on `main` isn't confirmed yet.** It's a human prerequisite (workflow.md).
- **wp-styleguide upstream:** path-traversal, redirect, rewrite-anchor and title-injection fixes are in [kingkool68/wp-styleguide#1](https://github.com/kingkool68/wp-styleguide/pull/1), not merged yet. Blammo keeps its own `styleguide_path` guard regardless.
