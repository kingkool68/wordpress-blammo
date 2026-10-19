# 0006 — WordPress MCP Adapter over WP-CLI STDIO

- Status: accepted (2026-10-18)

## Context
Agents need to inspect and change the local site (content, options, theme state). The WordPress MCP Adapter (`mcp-adapter` plugin, ≥0.7.1) exposes core Abilities API abilities over MCP. The older Automattic `wordpress-mcp` plugin is archived.

## Decision
- Install the `mcp-adapter` plugin on blammo.local only.
- Connect over **STDIO**: `bin/wp-local mcp-adapter serve --server=mcp-adapter-default-server --user=claude`, defined in `.mcp.json`. This avoids application passwords and HTTP sessions.
- Agents act as a dedicated `claude` administrator.
- Theme abilities live in `Blammo_Abilities`:
  - they're registered only when the environment type is `local`;
  - each needs `manage_options`;
  - they're exposed through `meta.mcp.public`.

## Consequences
- MCP only works while the Local site is running.
- STDIO has no auth beyond local shell access, which is acceptable for a local-only dev site.
- The plugin isn't a theme dependency, and the theme must not assume it exists.
- **Hardening rules** (details in local-environment.md):
  - every ability has a strict `input_schema` and annotations;
  - `permission_callback` re-checks capability **and** the `local` environment at execution time;
  - the `claude` user has a random password and no application passwords;
  - blammo.local is never exposed (Live Links/LAN) while the adapter's HTTP transport is active.

## Alternatives considered
- **HTTP transport with an application password:** rejected; it needs a credential in config, and the HTTP endpoint must be reachable.
- **Archived Automattic `wordpress-mcp`:** rejected; it's superseded by the adapter.
