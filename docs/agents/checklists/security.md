# WordPress security checklist

## Output
- [ ] Every Twig `{{ }}` is escaped for its context (autoescape is OFF): `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`.
- [ ] Values that hold pre-built HTML are built safely in PHP, and their names make that clear.
- [ ] PHP `echo` output is escaped late. `wp_json_encode` is used for data passed into JS.
- [ ] Inline SVG comes only from theme files with a fixed allowlisted name (no user-controlled path), or is sanitized.

## Input
- [ ] `$_GET`, `$_POST`, `$_REQUEST` and `$_SERVER` go through `wp_unslash()` and then the right `sanitize_*` function.
- [ ] Block attributes are typed in `block.json` and validated again in PHP before use.
- [ ] No file includes or Twig template names built from user input (path traversal).

## Authorization
- [ ] Every state-changing request checks a nonce (`check_admin_referer` / `wp_verify_nonce`) **and** a capability (`current_user_can`).
- [ ] REST routes have a real `permission_callback`, never `__return_true` for writes.
- [ ] Abilities:
  - they have a strict `permission_callback`;
  - `input_schema` constrains every field (types, enums, min/max);
  - local-only abilities check `wp_get_environment_type() === 'local'`;
  - destructive abilities are annotated `destructive: true`.

## Data
- [ ] SQL uses `$wpdb->prepare()`. No string-interpolated queries.
- [ ] Options and meta are sanitized on save, and transients can't be poisoned by user input.
- [ ] No secrets, tokens, emails or machine-specific paths in committed files (the repo is public). Prompt artifacts are redacted. `.env.local` (gitignored) holds the machine paths, and `.mcp.json` holds no credentials.

## Scripts and tooling
- [ ] `.claude/settings.json` allow rules are narrow: no wildcard allow for anything that runs arbitrary code, installs packages, or writes to blammo.local or GitHub beyond a feature-branch push / PR create.
- [ ] Shell scripts in `bin/` and `.claude/hooks/` quote variables, use `set -euo pipefail`, and don't `eval` input.
- [ ] Hooks fail closed where they guard something, e.g. the git guard.
- [ ] Dependencies are new only when justified, with a pinned or locked version.
