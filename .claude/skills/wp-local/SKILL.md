---
name: wp-local
description: How to safely operate on the local blammo.local WordPress site — via the blammo-wp MCP server (WordPress MCP Adapter) or bin/wp-local (WP-CLI with Local's PHP/MySQL). Use whenever you need to inspect or change local site content, options, users, plugins, or theme state.
---

# wp-local

Full background is in `docs/agents/local-environment.md`.

> The theme has no abilities yet (`Blammo_Abilities` comes in `feat/abilities`, after `chore/testing-ci`), so MCP only exposes core and plugin abilities. Use `bin/wp-local` for the rest.

## Preconditions
- The site must be running in the Local app. If the MySQL socket at `~/Library/Application Support/Local/run/$LOCAL_SITE_ID/mysql/mysqld.sock` is missing (`bin/wp-local` reports this), ask the user to start the site. Don't try to start MySQL yourself.
- `.env.local` exists in the main checkout. If it doesn't, copy `.env.local.example` and confirm the paths.
- blammo.local renders the **main checkout**, not your worktree.

## Choosing a tool
| Need | Use |
|---|---|
| Discover or run theme and site abilities | MCP `blammo-wp`: `mcp-adapter-discover-abilities` → `mcp-adapter-get-ability-info` → `mcp-adapter-execute-ability` |
| Anything else (posts, options, menus, users, plugins, cache, rewrite) | `bin/wp-local <wp-cli args>` |
| Automated tests | **Neither.** Use wp-env (`npm run env:start`) |

## Safety rules
1. **Read before write.** Inspect the current state first (`bin/wp-local option get …`, `post list`, etc.).
2. **Back up before anything destructive:** `bin/wp-local db export ~/blammo-backups/blammo-$(date +%F-%H%M).sql`. Destructive means deletes, `search-replace`, plugin or theme switches, user changes, `db import`, and bulk updates.
3. Never change `siteurl`/`home`, delete users, or change the `claude` user's role without the user's explicit OK.
4. Name agent-created content clearly (title prefix `[agent]`), and say what you created so the user can clean it up.
5. Never put credentials into files. STDIO MCP needs none.

## Common commands
```bash
bin/wp-local theme status blammo
bin/wp-local post create --post_type=page --post_title='[agent] Test page' --post_status=publish --porcelain
bin/wp-local rewrite flush
bin/wp-local mcp-adapter list
```
Calling `mcp-adapter serve` directly (e.g. piping JSON-RPC into it) bypasses MCP permission prompts. It always needs the human's approval; use the `blammo-wp` MCP tools instead.
