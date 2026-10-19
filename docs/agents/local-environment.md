# Local environment

There are two WordPress environments, and they serve different purposes:

| | blammo.local (Local by Flywheel) | wp-env (Docker) |
|---|---|---|
| Purpose | Manual dev, real content, MCP access for agents | Automated tests (PHPUnit, Playwright), CI parity |
| Theme source | The **main checkout** (`wp-content/themes/blammo`, on `main`) | The current directory (works in any worktree) |
| Access | `bin/wp-local`, MCP server `blammo-wp`, http://blammo.local | `npm run env:*`, http://localhost:8888 (tests on 8889) |
| Data | Persistent: back it up before destructive ops | Disposable |

> After pulling `main` in the main checkout, run `npm run build`, because `build/` is gitignored.
>
> Worktree code is **not** what blammo.local renders. To check a branch visually, use wp-env, or check the branch out in the main checkout.

## blammo.local facts
- WordPress 7.1.3, PHP 8.4.18, MySQL 8.4.0, Apache on :10034, Mailpit at http://localhost:10007
- Machine-specific values live in `.env.local` (gitignored; copy it from `.env.local.example`): `LOCAL_SITE_ID`, `LOCAL_PHP_BIN`, `LOCAL_WP_CLI`, `WP_PATH`. Find the site ID in `~/Library/Application Support/Local/sites.json`.
  - MySQL socket: `~/Library/Application Support/Local/run/$LOCAL_SITE_ID/mysql/mysqld.sock`
  - PHP config: `~/Library/Application Support/Local/run/$LOCAL_SITE_ID/conf/php` (its `php.ini` points `mysqli.default_socket` at that socket)
- `WP_ENVIRONMENT_TYPE` is `local`.
- No mu-plugins are needed. [wp-styleguide](https://github.com/kingkool68/wp-styleguide) is an optional regular plugin (`wp-content/plugins/wp-styleguide/`), installed by hand from a reviewed commit; without it `/styleguide/` 404s.
- Blammo is a **block theme**. Site-editor edits to templates, parts and global styles are stored in the database and override the theme files. Before judging a file change on blammo.local, check for overrides (`bin/wp-local post list --post_type=wp_template,wp_template_part,wp_global_styles`) and reset them in Site Editor → Templates / Patterns (after a `db export`).
- The site must be **running in the Local app**, or the socket doesn't exist and WP-CLI and MCP fail.

## WP-CLI: always `bin/wp-local`
Bare `wp` isn't on PATH. Local's bundled wp-cli uses `#!/usr/bin/env php`, which resolves to Homebrew PHP and can't reach Local's MySQL. `bin/wp-local` (added in `feat/mcp-adapter`):
- reads `.env.local`, which is gitignored (copy it from `.env.local.example`);
- exports `PHPRC` and `MYSQL_HOME`;
- runs Local's PHP binary on Local's wp-cli with `--path` set.

```bash
bin/wp-local option get siteurl
bin/wp-local db export ~/blammo-backups/blammo-$(date +%F-%H%M).sql   # before anything destructive
```

## MCP (WordPress MCP Adapter)
- `.mcp.json` defines `blammo-wp` as `bin/wp-local mcp-adapter serve --server=mcp-adapter-default-server --user=claude`, which is STDIO with no passwords.
- Agents act as the dedicated `claude` administrator, so their changes are attributable.
- The default server exposes three meta-tools: `mcp-adapter-discover-abilities`, `mcp-adapter-get-ability-info` and `mcp-adapter-execute-ability`. Discover, then inspect, then execute.
- Theme abilities live in `functions/class-blammo-abilities.php`:
  - they're registered **only when the environment type is `local`**;
  - each needs `manage_options` and uses `meta.mcp.public`.
- Use MCP for site state and content. Use `bin/wp-local` for anything an ability doesn't cover.

### MCP safety rules
- **Abilities:**
  - `input_schema` is strict: types, enums, min/max, no free-form strings passed into options or queries;
  - annotations are always set (`readonly`, `destructive`, `idempotent`);
  - `permission_callback` re-checks **both** `current_user_can( 'manage_options' )` and `wp_get_environment_type() === 'local'` at execution time, not only at registration.
- **The `claude` user** gets a long random password that nobody keeps, and **no application passwords**.
- **The adapter also exposes an HTTP transport** (`/wp-json/mcp/...`). Never expose blammo.local (Local "Live Links", LAN sharing) while the adapter is active.
- **Approval:** `bin/wp-local` wrapper commands that write or run code (`eval`, `db query`, `db import`, `search-replace`, plugin installs) and piping JSON-RPC into `mcp-adapter serve` are **not** pre-approved; the human approves each one. The wrapper refuses these unless `WP_LOCAL_ALLOW_DESTRUCTIVE=1` is set:
  - the subcommands `eval`, `eval-file`, `shell`, `db drop|reset|query|import|cli`, `site empty`;
  - the WP-CLI global flags `--exec`, `--require`, `--path`, `--ssh`, `--http`, `--context`. These run arbitrary PHP or retarget the command, even after a harmless subcommand like `option get`.
- **`db export`** writes only into `~/blammo-backups/`; the wrapper rejects any other path.

## wp-env
- `npm run env:start` runs `wp-env start`. `.wp-env.json` loads the theme from the current directory and installs wp-styleguide from a pinned commit zip (`https://github.com/kingkool68/wp-styleguide/archive/<sha>.zip`) in `plugins`.
- Run `npm run build` first: blocks register from `build/`.
- When several worktrees run at once, each must use unique ports:
  `WP_ENV_PORT=8890 WP_ENV_TESTS_PORT=8891 npm run env:start`
- Stop environments you started: `npm run env:stop`.
