#!/usr/bin/env bash
# PostToolUse(Edit|Write) hook: lint edited PHP files. Exit 2 sends the report back to Claude; never blocks otherwise.
input="$(cat)"
file="$(jq -r '.tool_input.file_path // empty' <<<"$input" 2>/dev/null)"
[[ "$file" == *.php && -f "$file" ]] || exit 0

if ! out="$(php -l "$file" 2>&1)"; then
	echo "php -l failed for $file:" >&2
	echo "$out" >&2
	exit 2
fi

root="$(git -C "$(dirname "$file")" rev-parse --show-toplevel 2>/dev/null)" || exit 0
if [[ -x "$root/vendor/bin/phpcs" && -f "$root/phpcs.xml.dist" ]]; then
	if ! out="$("$root/vendor/bin/phpcs" -q --standard="$root/phpcs.xml.dist" --report=emacs "$file" 2>&1)"; then
		echo "PHPCS issues in $file (fix, or run composer lint:fix):" >&2
		echo "$out" | head -n 40 >&2
		exit 2
	fi
fi
exit 0
