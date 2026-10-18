#!/usr/bin/env python3
"""PreToolUse(Bash) hook: block git/gh operations agents must never perform.

Defence in depth only. The real control is GitHub branch protection on `main`
(docs/agents/workflow.md). Commands that don't mention git/gh pass immediately.
Blocks (exit 2, reason on stderr):
  - git push to main/master: explicit (any ref path ending in main/master), HEAD/@ while on
    main, or implicit while on main
  - git push --mirror/--all/--delete/-d/--prune/--force/-f, +refspec, :refspec, shell expansion
  - git commit/push together with a branch switch; git commit/push when the branch is unknown
  - git commit on main/master (honours cd/pushd, -C, --git-dir, GIT_DIR)
  - git add -f; git -c alias.*/remote.*/push.*; git config alias.*; git update-ref main
  - git/gh inside xargs, interpreter one-liners, or piped into a shell
  - gh pr merge; mutating gh api calls on merges/refs/protection/rulesets; graphql merges
bash/sh -c, eval and backtick payloads are analysed recursively. Unparseable git/gh commands block.
"""
import json
import os
import re
import shlex
import subprocess
import sys

PROTECTED = {"main", "master"}
PROTECTED_REF = re.compile(r"(^|/)(main|master)$")
SEPARATORS = set("();|&\n")
TRANSPARENT = {"env", "command", "builtin", "nohup", "time", "exec", "nice", "sudo",
               "then", "do", "else", "elif", "if", "while", "until", "!", "{", "}"}
MENTIONS_GIT = re.compile(r"(^|[\s/;&|(`\"'])(git|gh)(\s|$)")


def block(reason):
    print(f"Blocked by .claude/hooks/guard-git.py: {reason}", file=sys.stderr)
    sys.exit(2)


def strip_heredocs(command):
    """Drop heredoc bodies: they are data (commit messages, file contents), not commands."""
    lines, out, terminator = command.split("\n"), [], None
    for line in lines:
        if terminator is not None:
            if line.strip() == terminator:
                terminator = None
                out.append(line.replace(line.strip(), ""))
            continue
        out.append(line)
        m = re.search(r"<<-?\s*(['\"]?)([A-Za-z_][A-Za-z0-9_]*)\1", line)
        if m:
            terminator = m.group(2)
    return "\n".join(out)


def segments_of(command):
    lexer = shlex.shlex(command, posix=True, punctuation_chars="();<>|&\n")
    lexer.whitespace = " \t\r"
    lexer.whitespace_split = True
    lexer.commenters = "#"
    try:
        tokens = list(lexer)
    except ValueError:
        block("could not parse a git/gh command (unbalanced quotes)")
    segment, prev_op = [], ""
    for tok in tokens:
        if set(tok) <= SEPARATORS:
            if segment:
                yield segment, prev_op
            segment, prev_op = [], tok
        else:
            segment.append(tok)
    if segment:
        yield segment, prev_op


def resolve(path, base):
    path = os.path.expanduser(os.path.expandvars(path))
    return path if os.path.isabs(path) else os.path.normpath(os.path.join(base, path))


def repo_of_git_dir(path, base):
    return os.path.dirname(resolve(path, base).rstrip("/"))


def current_branch(directory):
    try:
        return subprocess.run(
            ["git", "-C", directory, "rev-parse", "--abbrev-ref", "HEAD"],
            capture_output=True, text=True, timeout=5,
        ).stdout.strip()
    except Exception:
        return ""


def base(token):
    return os.path.basename(token)


def check_gh(seg):
    args = seg[1:]
    # gh [global flags] pr [flags] merge
    positional = [a for i, a in enumerate(args) if not a.startswith("-") and not (i > 0 and args[i - 1] in ("-R", "--repo"))]
    if positional[:2] == ["pr", "merge"]:
        block("agents never merge PRs; the human merges.")
    if positional[:1] == ["api"]:
        joined = " ".join(args)
        method = "GET"
        for i, a in enumerate(args):
            if a in ("-X", "--method") and i + 1 < len(args):
                method = args[i + 1].upper()
            elif a.startswith("--method="):
                method = a.split("=", 1)[1].upper()
        writes = method != "GET" or any(a in ("-f", "-F", "--field", "--raw-field", "--input") for a in args)
        if "graphql" in positional and re.search(r"(?i)merge|updateRef|deleteRef|branchProtection", joined):
            block("gh api graphql mutations touching merges/refs/protection are not allowed.")
        if writes and re.search(r"/merge\b|/merges\b|/git/refs|/protection|/rulesets", joined):
            block("mutating gh api calls on merges/refs/protection are not allowed.")


def analyse(command, cwd, depth=0):
    if depth > 3:
        block("command nesting too deep to analyse")
    command = strip_heredocs(command)
    if not MENTIONS_GIT.search(command):
        return
    # Command substitutions run even inside double quotes; analyse their contents separately.
    for inner in re.findall(r"`([^`]*)`", command) + re.findall(r"\$\(([^()]*)\)", command):
        analyse(inner, cwd, depth + 1)
    cd_dir = cwd
    git_ops = []  # (subcommand, args, directory)
    for seg, prev_op in segments_of(command):
        git_dir_env = None
        while seg:
            if re.fullmatch(r"[A-Za-z_][A-Za-z0-9_]*=.*", seg[0]):
                if seg[0].startswith("GIT_DIR="):
                    git_dir_env = repo_of_git_dir(seg[0].split("=", 1)[1], cd_dir)
                seg = seg[1:]
            elif seg[0] in TRANSPARENT:
                seg = seg[1:]
            elif seg[0] == "timeout":
                seg = seg[1:]
                while seg and (seg[0].startswith("-") or re.fullmatch(r"\d+(\.\d+)?[smhd]?", seg[0])):
                    seg = seg[1:]
            else:
                break
        if not seg:
            continue
        head = base(seg[0])
        rest_text = " ".join(seg[1:])

        if head in ("cd", "pushd"):
            cd_dir = resolve(seg[1], cd_dir) if len(seg) > 1 else os.path.expanduser("~")
            continue
        if head in ("bash", "sh", "zsh", "dash", "ksh"):
            flags = [i for i, t in enumerate(seg) if re.fullmatch(r"-[a-z]*c[a-z]*", t)]
            if flags:
                payload = [t for t in seg[flags[0] + 1:] if t != "--"]
                if payload:
                    analyse(payload[0], cd_dir, depth + 1)
            elif prev_op in ("|", "|&"):
                block("piping commands into a shell is not allowed when git/gh is involved.")
            continue
        if head == "eval":
            analyse(rest_text, cd_dir, depth + 1)
            continue
        if head == "xargs" and any(base(t) in ("git", "gh") for t in seg):
            block("git/gh via xargs is not allowed; run it directly.")
        if re.fullmatch(r"python[0-9.]*|perl|ruby|node|php|osascript", head) and re.search(r"\bgit\b|\bgh\b", rest_text):
            block("git/gh from an interpreter one-liner is not allowed; run it directly.")

        if head == "gh":
            check_gh(seg)
            continue
        if head != "git":
            continue

        i, directory = 1, git_dir_env or cd_dir
        while i < len(seg) and seg[i].startswith("-"):
            opt = seg[i]
            if opt.startswith("--git-dir="):
                directory = repo_of_git_dir(opt.split("=", 1)[1], directory)
                i += 1
            elif opt in ("-C", "-c", "--git-dir", "--work-tree", "--namespace") and i + 1 < len(seg):
                val = seg[i + 1]
                if opt == "-C":
                    directory = resolve(val, directory)
                elif opt == "--git-dir":
                    directory = repo_of_git_dir(val, directory)
                elif opt == "-c" and re.match(r"(?i)(alias|remote|push|url|branch)\.", val):
                    block(f"git -c {val.split('=')[0]} is not allowed.")
                i += 2
            else:
                i += 1
        if i < len(seg):
            git_ops.append((seg[i], seg[i + 1:], directory))

    subs = {op[0] for op in git_ops}
    if subs & {"switch", "checkout"} and subs & {"commit", "push"}:
        block("don't switch branches and commit/push in one command; run them separately.")

    for sub, args, directory in git_ops:
        if sub == "config" and any(re.match(r"(?i)(alias|remote\.[^.]+\.push|push)\.?", a) for a in args):
            block("changing git aliases or push config is not allowed.")
        if sub == "update-ref" and any(PROTECTED_REF.search(a) for a in args):
            block("updating main/master refs directly is not allowed.")
        if sub == "add" and any(a in ("-f", "--force") or re.fullmatch(r"-[a-zA-Z]*f[a-zA-Z]*", a) for a in args):
            block("git add --force could stage gitignored secrets.")

        if sub not in ("commit", "push"):
            continue
        branch = current_branch(directory)
        if not branch:
            block(f"could not determine the current branch in {directory}; refusing git {sub}. Use a literal path.")
        if sub == "commit":
            if branch in PROTECTED:
                block("committing on main/master is not allowed; create a worktree branch (docs/agents/workflow.md).")
            continue

        if any(re.search(r"[$`{}]", a) for a in args):
            block("shell expansion in git push arguments; use literal branch names.")
        for a in args:
            if a in ("--mirror", "--all", "--delete", "-d", "--prune", "--force", "-f", "--tags") \
                    or (re.fullmatch(r"-[a-zA-Z]+", a) and ("f" in a or "d" in a)):
                block(f"git push {a} is not allowed (only plain pushes and --force-with-lease).")
        refspecs = [a for a in args if not a.startswith("-")][1:]  # first positional is the remote
        for ref in refspecs:
            if ref.startswith(("+", ":")):
                block("force (+ref) and delete (:ref) refspecs are not allowed.")
            dest = ref.split(":")[-1]
            if PROTECTED_REF.search(dest) or (dest in ("HEAD", "@") and branch in PROTECTED):
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
