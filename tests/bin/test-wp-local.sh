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
ln -s "$test_home/elsewhere" "$backups/subdir-link"
touch "$backups/existing.sql"

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
expect refused "package install" -- package install some/package
expect refused "cli update" -- cli update
expect refused "config set" -- config set FOO bar --raw
expect refused "scaffold" -- scaffold plugin x
expect refused "search-replace" -- search-replace a b
expect refused "db clean" -- db clean --yes
expect refused "plugin install" -- plugin install https://example.com/x.zip --activate
expect refused "theme update" -- theme update --all
expect refused "cron event run" -- cron event run --due-now
expect refused "site delete" -- site delete 2

# The command must come first, so a space-separated flag value can't hide it.
expect rejected "flag before command" -- --user=claude eval 'echo 1;'
expect rejected "space-form flag value before command" -- --user admin eval x
expect rejected "space-form flag before db export" -- --user x db export /tmp/f
expect rejected "no command" --

# Flags before the subcommand and built-in aliases don't hide it.
expect refused "flag before db subcommand" -- db --quiet query 'DROP TABLE wp_users'
expect refused "flag before plugin subcommand" -- plugin --quiet install https://example.com/x.zip
expect refused "flag before site subcommand" -- site --yes empty
expect refused "flag before cron run" -- cron event --due-now run
expect refused "db connect (alias of cli)" -- db connect
expect refused "plugin upgrade (alias of update)" -- plugin upgrade --all
expect refused "theme uninstall (alias of delete)" -- theme uninstall x
expect refused "core update from a zip" -- core update https://example.com/x.zip
expect refused "core upgrade" -- core upgrade
expect refused "db search" -- db search '$wp$' --all-tables
expect rejected "db dump elsewhere (alias of export)" -- db dump /tmp/x.sql
expect rejected "flag before db export" -- db --porcelain export /tmp/x.sql
expect rejected "flag before db dump into backups" -- db --porcelain dump "$backups/x.sql"
expect allowed "db dump into backups" -- db dump "$backups/dump.sql"

# Secrets are rejected, even with the override.
expect rejected "unprefixed pass field" -- user list --fields=ID,pass
expect rejected "unprefixed activation_key field" -- user get 1 --field=activation_key
expect rejected "post password field" -- post list --fields=ID,password
expect rejected "user meta list without --keys" -- user meta list 1
expect allowed "user meta list with --keys" -- user meta list 1 --keys=nickname
expect rejected "user get without fields" -- user get 1
expect rejected "post get without fields" -- post get 1
expect allowed "post get with fields" -- post get 1 --fields=ID,post_title
expect rejected "application passwords meta" -- user meta get 1 _application_passwords
expect refused "--prompt" -- user list --prompt
expect refused "--prompt=fields" -- user list --prompt=fields
expect rejected "pass with a leading space" -- user list '--fields=ID, pass'
expect rejected "pass with a tab" -- user list "$(printf -- '--fields=ID,\tpass')"
expect rejected "password with trailing space" -- post list '--fields=ID,password '
expect rejected "app password list" -- user application-password list 1 '--fields=uuid, password'
expect rejected "empty --keys" -- user meta list 1 --keys=
expect rejected "--keys hidden in another flag" -- user meta list 1 '--skip-plugins= --keys=x'
expect rejected "--fields hidden in another flag" -- post get 1 '--skip-plugins= --fields=ID'
expect rejected "--field hidden in another flag" -- user get 1 '--skip-plugins= --field=ID'
expect rejected "user session list without fields" -- user session list 1 --format=json
expect rejected "user session list with token field" -- user session list 1 --fields=login_time,token
expect allowed "user session list with safe fields" -- user session list 1 --fields=login_time,expiration
expect rejected "db check with mysqlcheck flags" -- db check --plugin-dir=/tmp/p --default-auth=evil
expect rejected "db optimize with flags" -- db optimize --plugin-dir=/tmp/p
expect rejected "db repair with flags" -- db repair --default-auth=evil
expect allowed "db check without flags" -- db check
expect refused "core install-network (alias of multisite-convert)" -- core install-network
expect refused "server" -- server --config=/tmp/evil.ini
expect allowed "user list with safe fields" -- user list --fields=ID,user_login,roles
expect rejected "user_pass field" -- user list --fields=ID,user_pass
expect rejected "user_activation_key field" -- user get 1 --field=user_activation_key
expect rejected "session_tokens meta" -- user meta get 1 session_tokens
ALLOW=1 expect rejected "user_pass with override" -- user list --fields=user_pass

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
expect rejected "db export over an existing backup" -- db export "$backups/existing.sql"
expect rejected "db export via symlink/.." -- db export "$backups/subdir-link/../new.sql"
expect rejected "db export via ./" -- db export "$backups/./new.sql"
expect rejected "db export onto a symlink" -- db export "$backups/symlink.sql"
expect rejected "db export to a directory" -- db export "$backups/"
expect rejected "db export with --result-file" -- db export "$backups/blammo.sql" --result-file=/tmp/x.sql
expect rejected "db export --tables smuggling a flag" -- db export "$backups/blammo.sql" '--tables=wp_posts,--init-command=DROP TABLE wp_users'
expect rejected "db export --tables=--tab" -- db export "$backups/blammo.sql" --tables=--tab=/tmp
expect rejected "db export --exclude_tables smuggling a flag" -- db export "$backups/blammo.sql" --exclude_tables=x,--plugin-dir=/tmp
expect rejected "db export empty --tables" -- db export "$backups/blammo.sql" --tables=
expect rejected "db export with two paths" -- db export "$backups/a.sql" /tmp/b.sql
ALLOW=1 expect rejected "db export elsewhere, even with override" -- db export /tmp/blammo.sql

echo "wp-local guard: $passed passed, $failed failed"
[ "$failed" -eq 0 ]
