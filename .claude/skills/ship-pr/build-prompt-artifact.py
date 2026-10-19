#!/usr/bin/env python3
"""Build .agents/prompts/YYYY-MM-DD-<slug>.md from prompts captured by .claude/hooks/capture-prompt.sh.

Usage:
  build-prompt-artifact.py <slug> [--session ID] [--rebuild [--force]]
  build-prompt-artifact.py --scan <file>        # re-run the secret scan after redacting

Session selection (no --session): the single session with unshipped prompts whose captured cwd is
inside THIS worktree. Otherwise it refuses and lists candidates; re-run with --session.
After building, a {"shipped": slug} marker is appended so the next PR starts fresh. --rebuild
regenerates the artifact for the last shipped slug, including prompts captured since (follow-ups
during review), and moves the marker. It keeps the "## Clarifications" section, and refuses to
discard manual [REDACTED: ...] edits unless --force is given (re-apply them afterwards).

Exit codes: 0 ok, 1 usage/selection error, 3 possible secrets found (redact, then --scan).
The repo is PUBLIC: home paths are redacted automatically; anything flagged must be replaced
with [REDACTED: <kind>] — the only permitted edit to a prompt.
"""
import argparse
import datetime
import json
import os
import re
import subprocess
import sys

SECRET_PATTERNS = {
    "github token": r"gh[pousr]_[A-Za-z0-9]{20,}|github_pat_[A-Za-z0-9_]{20,}",
    "anthropic/openai key": r"sk-(ant-)?[A-Za-z0-9_-]{20,}",
    "aws key": r"AKIA[0-9A-Z]{16}",
    "slack token": r"xox[abpors]-[A-Za-z0-9-]{10,}",
    "private key": r"-----BEGIN [A-Z ]*PRIVATE KEY-----",
    "wp application password": r"\b(?=(?:[A-Za-z0-9]{4} ){5}[A-Za-z0-9]{4}\b)(?=[^\n]{0,29}\d)(?=[^\n]{0,29}[A-Z])[A-Za-z0-9]{4}( [A-Za-z0-9]{4}){5}\b",
    "stripe key": r"\b[sr]k_(live|test)_[A-Za-z0-9]{16,}",
    "google api key": r"\bAIza[0-9A-Za-z_-]{35}",
    "jwt": r"\beyJ[A-Za-z0-9_-]{10,}\.eyJ[A-Za-z0-9_-]{10,}\.",
    "credentials in url": r"://[^/\s:@]+:[^@\s/]+@",
    "password assignment": r"(?i)\b(password|passwd|pwd|secret|token|api[_-]?key)\b\s*(is\s+|[:=]\s*)\S{6,}",
    "email address": r"[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}",
}


def git(*args):
    return subprocess.run(["git", *args], capture_output=True, text=True, check=True).stdout.strip()


def scan(path):
    hits = []
    with open(path, encoding="utf-8") as fh:
        for n, line in enumerate(fh, 1):
            text = re.sub(r"\[REDACTED[^\]]*\]", "", line).replace("noreply@anthropic.com", "")
            for kind, pattern in SECRET_PATTERNS.items():
                if re.search(pattern, text):
                    hits.append(f"  line {n}: possible {kind}: {line.strip()[:100]}")
    if hits:
        print(f"Possible secrets in {path}. Replace each real secret with [REDACTED: <kind>], then run: "
              f"{sys.argv[0]} --scan {path}\nFalse positives (ordinary text) must NOT be redacted: leave them, "
              f"and note 'secret scan false positive: line N' in the PR body.", file=sys.stderr)
        print("\n".join(hits), file=sys.stderr)
        return 3
    return 0


def load_blocks(path):
    """Split a pending jsonl into blocks of prompt entries separated by shipped markers."""
    blocks, markers, current = [], [], []
    with open(path, encoding="utf-8") as fh:
        for raw in fh:
            try:
                entry = json.loads(raw)
            except ValueError:
                continue  # tolerate partial/corrupt lines
            if "shipped" in entry:
                blocks.append(current)
                markers.append(entry["shipped"])
                current = []
            elif entry.get("prompt"):
                current.append(entry)
    return blocks, markers, current


def fence_for(text):
    longest = max((len(m) for m in re.findall(r"~+", text)), default=0)
    return "~" * max(4, longest + 1)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("slug", nargs="?")
    parser.add_argument("--session")
    parser.add_argument("--rebuild", action="store_true")
    parser.add_argument("--force", action="store_true")
    parser.add_argument("--scan")
    opts = parser.parse_args()

    if opts.scan:
        return scan(opts.scan)
    if not opts.slug or not re.fullmatch(r"[a-z0-9][a-z0-9-]*", opts.slug):
        parser.error("slug is required and must be lowercase kebab-case")
    if opts.session and not re.fullmatch(r"[A-Za-z0-9_-]+", opts.session):
        parser.error("invalid session id")

    common_dir = git("rev-parse", "--path-format=absolute", "--git-common-dir")
    pending_dir = os.path.join(os.path.dirname(common_dir), ".agents", "prompts", ".pending")
    toplevel = git("rev-parse", "--show-toplevel")

    def entries_for(path):
        blocks, markers, current = load_blocks(path)
        if opts.rebuild:
            if markers and markers[-1] == opts.slug:
                return blocks[-1] + current
            return []
        return current

    def within(path, root):
        path, root = os.path.realpath(path), os.path.realpath(root)
        return path == root or path.startswith(root + os.sep)

    def is_local(entries):
        nested = os.path.join(toplevel, ".claude", "worktrees")
        return any(within(e.get("cwd") or "/nonexistent", toplevel)
                   and not within(e.get("cwd") or "/nonexistent", nested) for e in entries)

    if opts.session:
        files = [os.path.join(pending_dir, opts.session + ".jsonl")]
        if not os.path.isfile(files[0]):
            print(f"No capture file for session {opts.session} in {pending_dir}", file=sys.stderr)
            return 1
    else:
        files = sorted(
            (os.path.join(pending_dir, f) for f in os.listdir(pending_dir) if f.endswith(".jsonl"))
            if os.path.isdir(pending_dir) else []
        )
    candidates = [(f, e) for f in files if (e := entries_for(f))]
    if not opts.session:
        local = [(f, e) for f, e in candidates if is_local(e)]
        if len(local) == 1:
            candidates = local
        elif candidates:
            print("Can't tell which session's prompts belong to this worktree. Re-run with --session <id>, "
                  "choosing the one whose prompts match your conversation:", file=sys.stderr)
            for f, e in candidates:
                print(f"  {os.path.basename(f)[:-6]}  last {e[-1].get('ts')}: {e[-1]['prompt'][:80]!r}",
                      file=sys.stderr)
            return 1
    if not candidates:
        print("No captured prompts to ship. Write the artifact by hand with the verbatim prompt "
              "(see .agents/prompts/README.md).", file=sys.stderr)
        return 1
    if len(candidates) > 1:
        print("Several sessions have unshipped prompts. Re-run with --session <id>, choosing the one "
              "whose prompts match your conversation:", file=sys.stderr)
        for f, e in candidates:
            print(f"  {os.path.basename(f)[:-6]}  last {e[-1].get('ts')}: {e[-1]['prompt'][:80]!r}",
                  file=sys.stderr)
        return 1

    path, entries = candidates[0]
    session_id = os.path.basename(path)[:-6]
    date = datetime.date.today().isoformat()
    branch = git("rev-parse", "--abbrev-ref", "HEAD")
    out_dir = os.path.join(toplevel, ".agents", "prompts")
    os.makedirs(out_dir, exist_ok=True)
    out = os.path.join(out_dir, f"{date}-{opts.slug}.md")
    if os.path.exists(out) and not opts.rebuild:
        print(f"{out} already exists; use --rebuild to regenerate it.", file=sys.stderr)
        return 1
    tail = ""
    if opts.rebuild and os.path.exists(out):
        existing = open(out, encoding="utf-8").read()
        before, sep, after = existing.partition("\n## Clarifications")
        tail = sep + after if sep else ""
        if "[REDACTED" in before and not opts.force:
            print(f"{out} contains manual redactions that a rebuild would discard. Re-run with --force and "
                  f"re-apply them, then --scan.", file=sys.stderr)
            return 1

    home = os.path.expanduser("~")
    parts = [f"---\ndate: {date}\nbranch: {branch}\nsession_id: {session_id}\n---\n\n",
             f"# Prompts: {opts.slug}\n\nVerbatim prompts that produced this change, in order.\n"]
    for n, entry in enumerate(entries, 1):
        text = entry["prompt"].replace(home, "~").rstrip("\n")
        fence = fence_for(text)
        parts.append(f"\n## Prompt {n} ({entry.get('ts', 'unknown time')})\n\n{fence}text\n{text}\n{fence}\n")
    with open(out, "w", encoding="utf-8") as fh:
        fh.write("".join(parts) + tail)

    ts = datetime.datetime.now(datetime.timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    marker = json.dumps({"shipped": opts.slug, "ts": ts}) + "\n"
    if opts.rebuild:
        # Move the last marker to the end so follow-up prompts count as shipped with this slug.
        with open(path, encoding="utf-8") as fh:
            lines = fh.readlines()
        for i in range(len(lines) - 1, -1, -1):
            try:
                if json.loads(lines[i]).get("shipped") == opts.slug:
                    del lines[i]
                    break
            except ValueError:
                continue
        with open(path, "w", encoding="utf-8") as fh:
            fh.writelines(lines + [marker])
    else:
        with open(path, "a", encoding="utf-8") as fh:
            fh.write(marker)
    print(out)
    return scan(out)


if __name__ == "__main__":
    sys.exit(main())
