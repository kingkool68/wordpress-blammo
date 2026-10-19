---
date: 2026-10-18
branch: chore/agent-infrastructure
session_id: initial-planning-session (captured by hand; the prompt-capture hook didn't exist yet)
---

# Prompts: agentic-workflow-setup

Verbatim prompts that produced this change, in order. This artifact covers the overall project plan. Later PRs in the
plan (`feat/theme-scaffold`, `chore/testing-ci`, `feat/mcp-adapter`) reference it.

## Prompt 1

~~~~text
I want to build a fully agentic workflow for developing a WordPress theme called Blammo. My preferred way for building a WordPress theme is https://github.com/kingkool68/wordpress-rh-starter-theme 

The git repository URL for this project (just a WordPress theme) is at https://github.com/kingkool68/wordpress-blammo 

Help me structure the project to save context as MD files so other agents can quickly get up to speed with the project. I'll need additional sub agents to perform work like adversarial code review, system architechture, security, and acessibility. Agents should open pull requests and include the original prompt as an artifact. I'll need unit tests and maybe e2e tests for different functionality. I would like to hook up the WordPress MCP adapter so agents can work with my local WordPress site. Ask me any clarifying questions
~~~~

## Prompt 2

~~~~text
The blammo.local socket is ~/Library/Application Support/Local/run/qQNo-k_Jg/mysql/mysqld.sock
~~~~

## Prompt 3

~~~~text
Sprig can be added in the mu-plugins directory
~~~~

## Prompt 4

~~~~text
The styleguide is a plugin at https://github.com/kingkool68/wp-styleguide
~~~~

## Prompt 5

~~~~text
open a PR on kingkool68/wp-styleguide to fix
~~~~

(Prompt 5 produced [kingkool68/wp-styleguide#1](https://github.com/kingkool68/wp-styleguide/pull/1), which fixes the path traversal, redirect, rewrite-anchor and title-injection bugs found while documenting the plugin.)

## Clarifications

| Question | Answer |
|---|---|
| How should Blammo handle Sprig and ACF Pro dependencies? | Sprig + native blocks |
| Where should PHPUnit and e2e tests run? | wp-env everywhere |
| How should the original prompt be included as a PR artifact? | Committed file + PR body |
| When should the specialist review agents run? | Locally before PR |
| How should the build handle block editor JS alongside Gulp? | wp-scripts only |
| Which WordPress user should MCP / WP-CLI agents act as? | Dedicated 'claude' admin |
| Should the theme adopt theme.json? | No theme.json |
| Should agents work in isolated git worktrees? | Yes, worktrees |

The approved plan was: bootstrap `main` → PR 1 agent infrastructure → PR 2 theme scaffold → PR 3 testing + CI → PR 4 MCP adapter → backlog issues.
