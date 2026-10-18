#!/usr/bin/env bash
# UserPromptSubmit hook: append the verbatim prompt to .agents/prompts/.pending/<session_id>.jsonl
# in the MAIN checkout (shared by all worktrees). Never blocks the prompt: always exits 0.
input="$(cat)"
{
	cwd="$(jq -r '.cwd // empty' <<<"$input")"
	session_id="$(jq -r '.session_id // "unknown"' <<<"$input" | tr -cd 'A-Za-z0-9_-')"
	common_dir="$(git -C "${cwd:-$CLAUDE_PROJECT_DIR}" rev-parse --path-format=absolute --git-common-dir)"
	[[ -n "$common_dir" ]] || exit 0
	pending_dir="$(dirname "$common_dir")/.agents/prompts/.pending"
	mkdir -p "$pending_dir"
	jq -c --arg ts "$(date -u +%FT%TZ)" '{ts: $ts, session_id: .session_id, cwd: .cwd, prompt: .prompt}' \
		<<<"$input" >>"$pending_dir/${session_id:-unknown}.jsonl"
} 2>/dev/null
exit 0
