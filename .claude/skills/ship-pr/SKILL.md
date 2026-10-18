---
name: ship-pr
description: Finish a Blammo task — commit, run checks, build the redacted prompt artifact, run reviewer subagents in parallel and fix findings, update status docs, push, and open a PR. Use at the end of every task. Never merges.
---

# ship-pr

Run these steps in order. Don't skip any. If a step fails, fix the problem and continue. Stop and ask the user only if you're truly blocked. Full rationale is in `docs/agents/workflow.md`.

## 0. Preconditions
- You're in a worktree on a `<type>/<slug>` branch, not `main` (`git branch --show-current`).
- **Commit all work** with Conventional Commits. Don't use `git stash`: the stash is shared across worktrees.
- `git fetch origin && git rebase origin/main` is clean.

## 1. Checks
Run each command that exists in `package.json` / `composer.json` (see `docs/agents/status.md`):
```bash
npm run lint && composer lint && npm run test:js && npm run test:php && npm run test:e2e
```
They must all pass. Record the commands and their pass counts for the PR body.

## 2. Docs (before the reviews, so reviewers check them)
- Update `docs/agents/status.md`: what exists, in flight, next, known gaps, and the "Last updated" line.
- Update any doc whose subject changed. Commit.

## 3. Prompt artifact (before the reviews)
```bash
python3 .claude/skills/ship-pr/build-prompt-artifact.py <slug>
```
- **Session not found:** it auto-selects only a session whose prompts were captured inside this worktree. Otherwise it lists candidates; re-run with `--session <id>`, choosing the session whose prompts match your conversation.
- **Possible secrets (exit 3):** the repo is **public**. Replace each *real* secret with `[REDACTED: <kind>]`, then run `--scan <file>`.
  - Redaction is the **only** permitted edit. Never paraphrase.
  - Never redact ordinary text that was flagged by mistake. Leave it, and note "secret scan false positive: line N" in the PR body.
  - You may delete prompts that are unrelated to this PR.
- **Nothing captured:** write the file by hand, in the same format, with the prompt copied exactly.
- Add a `## Clarifications` section with the questions you asked and the user's answers.
- **Regenerating:** use `--rebuild`, for example after follow-up prompts during review. It includes the new prompts and keeps `## Clarifications`. If you redacted by hand, it refuses unless you pass `--force`; then re-apply the redactions and `--scan` again.
- Commit the artifact.

## 4. Reviews (in parallel)
1. List the changed files: `git diff --name-only origin/main...HEAD`.
2. Choose reviewers with the routing table in `docs/agents/workflow.md` §8. `adversarial-reviewer` always runs.
3. Launch **all chosen reviewers in a single message** (parallel Agent calls). Give each one:
   - the worktree path and branch;
   - the prompt artifact path;
   - the instruction "Review `git diff origin/main...HEAD`".
4. Fix every `blocking` and `major` finding, commit the fixes, then re-run the checks and the reviewers that raised them. Do **at most 2 rounds**.
5. Keep a table for the PR body: reviewer, verdict, then findings fixed / open (with severity).

## 5. Push and open the PR
```bash
git push -u origin <branch>
gh pr create --base main --head <branch> --title "<conventional title>" --body-file <tmpfile>
```
- Fill in `.github/pull_request_template.md` into the temp file first.
- In "Original prompt", paste the redacted artifact text inside `<details>`, using the same fence as the artifact, and link the artifact file.
- Fill in "Review summary" and "Test evidence" with real results. Never claim a check passed if you didn't run it.
- End every commit with the Co-Authored-By trailer from your system instructions.
- Report the PR URL to the user. **Don't merge.**
