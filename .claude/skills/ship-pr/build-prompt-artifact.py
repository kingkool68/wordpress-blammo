#!/usr/bin/env python3
"""Build .agents/prompts/YYYY-MM-DD-<slug>.md from prompts captured by .claude/hooks/capture-prompt.sh.

Usage:
  build-prompt-artifact.py <slug> [--session ID] [--rebuild]
  build-prompt-artifact.py --scan <file>        # re-run the secret scan after redacting

Session selection (no --session): sessions with unshipped prompts whose captured cwd is inside this
worktree; else the only session with unshipped prompts; else it refuses and lists candidates.
After building, a {"shipped": slug} marker is appended so the next PR starts fresh. --rebuild
regenerates the artifact for the last shipped slug (e.g. after a review round) without a new marker.

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
    "wp application password": r"\b[A-Za-z0-9]{4}( [A-Za-z0-9]{4}){5}\b",
    "password assignment": r"(?i)\b(password|passwd|secret|token|api[_-]?key)\b\s*[:=]\s*\S{6,}",
    "email address": r"[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}",
}


def git(*args):
    return subprocess.run(["git", *args], capture_output=True, text=True, check=True).stdout.strip()


def scan(path):
    hits = []
    with open(path, encoding="utf-8") as fh:
        for n, line in enumerate(fh, 1):
            for kind, pattern in SECRET_PATTERNS.items():
                if re.search(pattern, line) and "[REDACTED" not in line and "noreply@anthropic.com" not in line:
                    hits.append(f"  line {n}: possible {kind}: {line.strip()[:100]}")
    if hits:
        print(f"Possible secrets in {path}. Replace each with [REDACTED: <kind>] (or confirm it's safe "
              f"to publish), then run: {sys.argv[0]} --scan {path}", file=sys.stderr)
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
                return blocks[-1]
            return []
        return current

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
    if len(candidates) > 1:
        local = [(f, e) for f, e in candidates
                 if any((x.get("cwd") or "").startswith(toplevel) for x in e)]
        if len(local) == 1:
            candidates = local
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

    home = os.path.expanduser("~")
    parts = [f"---\ndate: {date}\nbranch: {branch}\nsession_id: {session_id}\n---\n\n",
             f"# Prompts: {opts.slug}\n\nVerbatim prompts that produced this change, in order.\n"]
    for n, entry in enumerate(entries, 1):
        text = entry["prompt"].replace(home, "~").rstrip("\n")
        fence = fence_for(text)
        parts.append(f"\n## Prompt {n} ({entry.get('ts', 'unknown time')})\n\n{fence}text\n{text}\n{fence}\n")
    with open(out, "w", encoding="utf-8") as fh:
        fh.write("".join(parts))

    if not opts.rebuild:
        with open(path, "a", encoding="utf-8") as fh:
            ts = datetime.datetime.now(datetime.timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
            fh.write(json.dumps({"shipped": opts.slug, "ts": ts}) + "\n")
    print(out)
    return scan(out)


if __name__ == "__main__":
    sys.exit(main())
