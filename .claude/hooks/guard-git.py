#!/usr/bin/env python3
"""PreToolUse(Bash) hook: block git/gh operations agents must never perform.

Defence in depth only. The real control is GitHub branch protection on `main`
(docs/agents/workflow.md). Blocks (exit 2, reason on stderr):
  - git push to main/master (explicit, HEAD/@ while on main, or implicit while on main)
  - git push --mirror/--all/--delete/-d/--prune/--force/-f, +refspec, :refspec
  - git push with shell expansion ($, backticks, braces) in its arguments
  - git commit/push in a command that also switches or checks out a branch
  - git commit/push when the branch can't be determined (fails closed)
  - git commit while on main/master
  - git add -f/--force (could stage gitignored secrets)
  - git -c alias.*; git hidden in xargs
  - gh pr merge (any flags), gh api calls touching merges/refs/protection/rulesets
bash/sh/zsh -c and eval payloads are analysed recursively. Unparseable input blocks.
"""
import json
import os
import re
import shlex
import subprocess
import sys

PROTECTED = {"main", "master"}
OPERATORS = {"&&", "||", ";", "|", "&", "(", ")", "\n", ";;", "|&"}


def block(reason):
    print(f"Blocked by .claude/hooks/guard-git.py: {reason}", file=sys.stderr)
    sys.exit(2)


def tokenize(command):
    """Shell-ish tokenization: operators split commands only outside quotes."""
    lexer = shlex.shlex(command, posix=True, punctuation_chars="();<>|&\n")
    lexer.whitespace = " \t\r"
    lexer.whitespace_split = True
    lexer.commenters = ""
    return list(lexer)


def segments_of(command):
    try:
        tokens = tokenize(command)
    except ValueError:
        block("could not parse command (unbalanced quotes)")
    segment = []
    for tok in tokens:
        if tok in OPERATORS or set(tok) <= set("();|&\n"):
            if segment:
                yield segment
            segment = []
        else:
            segment.append(tok)
    if segment:
        yield segment


def resolve(path, base):
    path = os.path.expanduser(os.path.expandvars(path))
    return path if os.path.isabs(path) else os.path.normpath(os.path.join(base, path))


def current_branch(directory):
    try:
        return subprocess.run(
            ["git", "-C", directory, "rev-parse", "--abbrev-ref", "HEAD"],
            capture_output=True, text=True, timeout=5,
        ).stdout.strip()
    except Exception:
        return ""


def is_cmd(token, name):
    return os.path.basename(token) == name


def analyse(command, cwd, depth=0):
    if depth > 3:
        block("command nesting too deep to analyse")
    cd_dir = cwd
    git_ops = []  # (subcommand, args, directory)
    for seg in segments_of(command):
        # Strip leading env assignments and transparent prefixes.
        while seg and (re.fullmatch(r"[A-Za-z_][A-Za-z0-9_]*=.*", seg[0]) or seg[0] in ("env", "command", "builtin", "nohup", "time", "{", "}")):
            seg = seg[1:]
        if not seg:
            continue
        head = seg[0]

        if head == "cd":
            cd_dir = resolve(seg[1], cd_dir) if len(seg) > 1 else os.path.expanduser("~")
            continue
        if head in ("bash", "sh", "zsh") and "-c" in seg:
            idx = seg.index("-c")
            if idx + 1 < len(seg):
                analyse(seg[idx + 1], cd_dir, depth + 1)
            continue
        if head == "eval":
            analyse(" ".join(seg[1:]), cd_dir, depth + 1)
            continue
        if head == "xargs" and any(is_cmd(t, "git") or is_cmd(t, "gh") for t in seg):
            block("git/gh via xargs is not allowed; run it directly.")

        if is_cmd(head, "gh"):
            rest = " ".join(seg[1:])
            if re.search(r"\bpr\s+(.*\s)?merge\b", rest):
                block("agents never merge PRs; the human merges.")
            if "api" in seg and re.search(r"/merge\b|/git/refs|/protection|/rulesets|/merges\b", rest):
                block("gh api calls that merge PRs or change refs/protection are not allowed.")
            continue

        if not is_cmd(head, "git"):
            continue
        i, directory = 1, cd_dir
        while i < len(seg) and seg[i].startswith("-"):
            if seg[i] in ("-C", "-c", "--git-dir", "--work-tree") and i + 1 < len(seg):
                if seg[i] == "-C":
                    directory = resolve(seg[i + 1], directory)
                if seg[i] == "-c" and seg[i + 1].lower().startswith("alias."):
                    block("git -c alias.* is not allowed.")
                i += 2
            else:
                i += 1
        if i < len(seg):
            git_ops.append((seg[i], seg[i + 1:], directory))

    subs = {op[0] for op in git_ops}
    if subs & {"switch", "checkout"} and subs & {"commit", "push"}:
        block("don't switch branches and commit/push in one command; run them separately.")

    for sub, args, directory in git_ops:
        if sub == "add" and any(a in ("-f", "--force") or re.fullmatch(r"-[a-zA-Z]*f[a-zA-Z]*", a) for a in args):
            block("git add --force could stage gitignored secrets.")

        if sub in ("commit", "push"):
            branch = current_branch(directory)
            if not branch:
                block(f"could not determine the current branch in {directory}; refusing git {sub}.")
            if sub == "commit" and branch in PROTECTED:
                block("committing on main/master is not allowed; create a worktree branch (docs/agents/workflow.md).")

        if sub == "push":
            if any(re.search(r"[$`{}]", a) for a in args):
                block("shell expansion in git push arguments; use literal branch names.")
            for a in args:
                if a in ("--mirror", "--all", "--delete", "-d", "--prune", "--force", "-f") \
                        or (re.fullmatch(r"-[a-zA-Z]+", a) and ("f" in a or "d" in a)):
                    block(f"git push {a} is not allowed (only plain pushes and --force-with-lease).")
            refspecs = [a for a in args if not a.startswith("-")][1:]  # first positional is the remote
            for ref in refspecs:
                if ref.startswith("+") or ref.startswith(":"):
                    block("force (+ref) and delete (:ref) refspecs are not allowed.")
                dest = ref.split(":")[-1].removeprefix("refs/heads/")
                if dest in PROTECTED or (dest in ("HEAD", "@") and branch in PROTECTED):
                    block(f"pushing to '{dest}' is not allowed; open a PR from a feature branch.")
            if not refspecs and branch in PROTECTED:
                block("pushing while on main/master is not allowed; work in a feature branch worktree.")


try:
    data = json.load(sys.stdin)
    command = data.get("tool_input", {}).get("command", "") or ""
    cwd = data.get("cwd") or os.getcwd()
except Exception:
    block("could not parse hook input")

analyse(command, cwd)
sys.exit(0)
