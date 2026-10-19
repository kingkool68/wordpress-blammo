#!/usr/bin/env bash
#
# Tests for bin/wp-local's guard. They run with WP_LOCAL_CHECK_ONLY=1 and a
# temporary HOME, so they need neither the Local app nor a database.
#
# Usage: tests/bin/test-wp-local.sh

set -uo pipefail

repo_dir="$(cd "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd -P)"
wrapper="$repo_dir/bin/wp-local"

test_home="$(mktemp -d)"
trap 'rm -rf "$test_home"' EXIT
backups="$test_home/blammo-backups"
mkdir -p "$backups" "$test_home/elsewhere"
ln -s "$test_home/elsewhere" "$test_home/link-to-elsewhere"
ln -s "$test_home/elsewhere/x.sql" "$backups/symlink.sql"

passed=0
failed=0

# expect <allowed|refused|rejected> <description> -- <args...>
# allowed = exit 0, refused = exit 2 (needs human approval), rejected = exit 1.
expect() {
	local want="$1" desc="$2"
	shift 3
	local status=0
	HOME="$test_home" WP_LOCAL_CHECK_ONLY=1 WP_LOCAL_ALLOW_DESTRUCTIVE="${ALLOW:-}" \
		"$wrapper" "$@" >/dev/null 2>&1 || status=$?
	local got
	case "$status" in
		0) got=allowed ;;
		2) got=refused ;;
		*) got=rejected ;;
	esac
	if [ "$got" = "$want" ]; then
		passed=$((passed + 1))
	else
		failed=$((failed + 1))
		echo "FAIL: $desc: wanted $want, got $got (exit $status)"
	fi
}

# Read-only commands pass.
expect allowed "option get" -- option get siteurl
expect allowed "post list with flags" -- post list --post_type=post --post_status=publish --format=count
expect allowed "mcp-adapter serve" -- mcp-adapter serve --server=mcp-adapter-default-server --user=claude

# Code-running and data-wiping commands are refused.
expect refused "eval" -- eval 'echo 1;'
expect refused "eval-file" -- eval-file /tmp/x.php
expect refused "shell" -- shell
expect refused "db query" -- db query 'SELECT 1'
expect refused "db import" -- db import dump.sql
expect refused "db drop" -- db drop --yes
expect refused "db reset" -- db reset --yes
expect refused "db cli" -- db cli
expect refused "site empty" -- site empty --yes
expect refused "eval after a global flag" -- --user=claude eval 'echo 1;'

# Dangerous global flags are refused, even after a harmless command.
expect refused "--exec" -- option get siteurl --exec='echo 1;'
expect refused "--require" -- option get siteurl --require=/tmp/x.php
expect refused "--require (space form)" -- option get siteurl --require /tmp/x.php
expect refused "--path" -- option get siteurl --path=/tmp
expect refused "--ssh" -- option get siteurl --ssh=prod
expect refused "--http" -- option get siteurl --http=https://example.com
expect refused "--context" -- option get siteurl --context=admin
expect refused "alias" -- @prod option get siteurl

# The override lets a human-approved command through.
ALLOW=1 expect allowed "db query with override" -- db query 'SELECT 1'
ALLOW=1 expect allowed "eval with override" -- eval 'echo 1;'

# db export only writes into ~/blammo-backups/.
expect allowed "db export into backups" -- db export "$backups/blammo.sql"
expect allowed "db export with --tables" -- db export "$backups/blammo.sql" --tables=wp_posts
expect rejected "db export with no file" -- db export
expect rejected "db export to stdout" -- db export -
expect rejected "db export elsewhere" -- db export "$test_home/elsewhere/blammo.sql"
expect rejected "db export relative path" -- db export blammo.sql
expect rejected "db export with .." -- db export "$backups/../elsewhere/blammo.sql"
expect rejected "db export into a symlinked dir" -- db export "$test_home/link-to-elsewhere/blammo.sql"
expect rejected "db export onto a symlink" -- db export "$backups/symlink.sql"
expect rejected "db export to a directory" -- db export "$backups/"
expect rejected "db export with --result-file" -- db export "$backups/blammo.sql" --result-file=/tmp/x.sql
expect rejected "db export with two paths" -- db export "$backups/a.sql" /tmp/b.sql
ALLOW=1 expect rejected "db export elsewhere, even with override" -- db export /tmp/blammo.sql

echo "wp-local guard: $passed passed, $failed failed"
[ "$failed" -eq 0 ]
