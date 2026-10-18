# 0005 — Local reviewer agents and committed prompt artifacts

- Status: accepted (2026-10-18)

## Context
The project is developed mainly by AI agents. Changes need independent scrutiny, plus a durable record of *what was asked*, so humans and later agents can judge intent against implementation.

## Decision
- Every task runs in its own branch and git worktree, and ends in a PR. Agents never merge or push to `main`.
- Before a PR is opened, the relevant specialist subagents (adversarial, security, accessibility, architect) review it locally. Blocking and major findings get fixed, and the PR body carries a review summary.
- A `UserPromptSubmit` hook captures prompts verbatim to `.agents/prompts/.pending/`. The PR commits a `.agents/prompts/YYYY-MM-DD-<slug>.md` artifact and quotes it in a `<details>` block in the PR body.
- The repo is public, so artifacts are redacted (secrets, emails, home paths), and that is the only edit allowed.
- Enforcement:
  - GitHub branch protection on `main` is the real control;
  - `.claude/hooks/guard-git.py` and narrow permission allow rules are defence in depth.
- `docs/agents/status.md` is updated by every PR, so it works as the hand-off note.

## Consequences
- There's no Anthropic API key in CI, because reviews happen in the developer's session.
- The repo history keeps every originating prompt.
- Reviews cost tokens on every PR. The reviewers are chosen by which paths changed, to keep that cost down.

## Alternatives considered
- **Reviews in CI with `claude-code-action`:** deferred, because it needs an API key secret. It can be added later on top of local reviews.
- **Prompt only in the PR body:** rejected because PR bodies are editable and live outside git history.
