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
- Machine-specific values live in `.env.local` (gitignored; copy it from `.env.local.example`): `LOCAL_SITE_ID`, `LOCAL_PHP_BIN`, `LOCAL_WP_CLI`, `WP_PATH`, and optionally `LOCAL_MYSQL_BIN`. Find the site ID in `~/Library/Application Support/Local/sites.json`.
  - MySQL socket: `~/Library/Application Support/Local/run/$LOCAL_SITE_ID/mysql/mysqld.sock`
  - PHP config: `~/Library/Application Support/Local/run/$LOCAL_SITE_ID/conf/php` (its `php.ini` points `mysqli.default_socket` at that socket)
- `WP_ENVIRONMENT_TYPE` is `local`.
- No mu-plugins are needed. [wp-styleguide](https://github.com/kingkool68/wp-styleguide) is an optional regular plugin (`wp-content/plugins/wp-styleguide/`), installed by hand from a reviewed commit; without it `/styleguide/` 404s.
- Blammo is a **block theme**. Site-editor edits to templates, parts and global styles are stored in the database and override the theme files. Before judging a file change on blammo.local, check for overrides (`bin/wp-local post list --post_type=wp_template,wp_template_part,wp_global_styles`, then keep only posts whose `wp_theme` term is `blammo`; an empty `wp_global_styles` post, `{"version":3,"isGlobalStylesUserThemeJSON":true}`, is created when the Site Editor opens and isn't an override) and reset them in Site Editor → Templates / Patterns (after a `db export`).
- The site must be **running in the Local app**, or the socket doesn't exist and WP-CLI and MCP fail.

## WP-CLI: always `bin/wp-local`
Bare `wp` isn't on PATH. Local's bundled wp-cli uses `#!/usr/bin/env php`, which resolves to Homebrew PHP and can't reach Local's MySQL. `bin/wp-local`:
- reads `.env.local`, which is gitignored (copy it from `.env.local.example`). It parses `KEY=VALUE` lines and never sources the file. It always reads the **main checkout's** `.env.local` (via the git common dir) and ignores any copy in a worktree;
- exports `PHPRC`, `MYSQL_HOME` and `WP_CLI_CONFIG_PATH` (Local's wp-cli `config.yaml`, which makes `wp db *` honour `MYSQL_HOME`), and puts `LOCAL_MYSQL_BIN` on `PATH` for `mysqldump`;
- runs Local's PHP binary on Local's wp-cli with `--path` set, from `/`, so no `wp-cli.yml` in a checkout (which could `require:` PHP) is loaded.
- The WP-CLI command must be the **first** argument (`bin/wp-local option get siteurl`, not `bin/wp-local --user=x option get …`). Because it runs from `/`, **file arguments must be absolute paths**.
- Guard tests: `tests/bin/test-wp-local.sh`. `WP_LOCAL_CHECK_ONLY=1` runs only the guard and exits.

```bash
bin/wp-local option get siteurl
bin/wp-local db export ~/blammo-backups/blammo-$(date +%F-%H%M).sql   # before anything destructive
```

## MCP (WordPress MCP Adapter)
- `.mcp.json` defines `blammo-wp` as `bin/wp-local mcp-adapter serve --server=mcp-adapter-default-server --user=claude`, which is STDIO with no passwords.
- Agents act as the dedicated `claude` administrator, so their changes are attributable.
- The default server exposes three meta-tools: `mcp-adapter-discover-abilities`, `mcp-adapter-get-ability-info` and `mcp-adapter-execute-ability`. Discover, then inspect, then execute.
- Theme abilities will live in `functions/class-blammo-abilities.php` (`feat/abilities`; none exist yet):
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
  - the commands `eval`, `eval-file`, `shell`, `package`, `cli`, `config`, `scaffold`, `search-replace`, `db drop|reset|query|import|cli|clean|search`, `core update|download|install|multisite-*`, `plugin|theme install|update|delete`, `cron event run`, `site empty|delete`. Subcommands are found the way WP-CLI finds them (ignoring `--flags` in between), and WP-CLI's aliases (`dump`, `connect`, `upgrade`, `uninstall`) are mapped first;
  - the WP-CLI global flags `--exec`, `--require`, `--path`, `--ssh`, `--http`, `--context`, `--prompt`. These run arbitrary PHP or retarget the command, even after a harmless subcommand like `option get`;
  - WP-CLI aliases (`@name`), which can retarget the command at another site.
- The guard is **defence in depth**, a denylist. The real gate is the permission prompt; other writes (`option update`, `post delete`, `user create`) pass the wrapper and rely on it.
- **Best effort** against secrets, even with the override: arguments mentioning `user_pass`, `user_activation_key` or `session_tokens`, `--field(s)` values `pass`, `password` or `activation_key` (with or without a `user_`/`post_` prefix), `_application_passwords`, `user get` / `post get` without `--field(s)`, and `user meta list` without `--keys` are rejected. Backups in `~/blammo-backups/` hold the whole database, password hashes included. Other options and private posts **are** readable, so don't paste command output into PRs or prompt artifacts without checking it.
- **`db export`** writes only **new** files (mode 0600) into `~/blammo-backups/`, even with the override. The wrapper rejects relative paths, `.`/`..` components, other directories, existing files, symlinks, stdout (`-`), and any flag except `--tables`, `--exclude_tables`, `--porcelain`, `--add-drop-table` and `--quiet`, since mysqldump flags such as `--result-file` would pass straight through. `--tables` / `--exclude_tables` values must be plain table names, because WP-CLI splits them on commas into separate mysqldump arguments. Flags go after the file path.

## wp-env
- `npm run env:start` runs `wp-env start`. `.wp-env.json` loads the theme from the current directory and installs wp-styleguide from a pinned commit zip (`https://github.com/kingkool68/wp-styleguide/archive/<sha>.zip`) in `plugins`. Its `config` sets `WP_DEVELOPMENT_MODE` to `'theme'`, so core re-reads `patterns/*.php` and `theme.json` on every request instead of caching them per theme `Version`. Set the same constant in blammo.local's `wp-config.php` (a human-approved setup step, see `status.md`); until then, a new pattern won't show there without a `Version` bump.
- Run `npm run build` first: blocks register from `build/`.
- When several worktrees run at once, each must use unique ports:
  `WP_ENV_PORT=8890 WP_ENV_TESTS_PORT=8891 npm run env:start`
- Stop environments you started: `npm run env:stop`.
