---
date: 2026-10-18
branch: feat/mcp-adapter
session_id: 3026f8cc-1b65-4a2d-8847-642fede61b7c
---

# Prompts: mcp-adapter

Verbatim prompts that produced this change, in order.

## Prompt 1 (2026-10-19T01:27:47Z)

~~~~text
Conect to the local site and tell me how many published posts there are
~~~~

## Prompt 2 (2026-10-19T01:28:41Z)

~~~~text
Do option 2. Let's get that MCP adapter set up
~~~~

## Clarifications

- Before prompt 2, the agent offered two options for reaching the local site: (1) a one-off exception to run bare `wp` for a read-only count, or (2) build `feat/mcp-adapter` so `bin/wp-local` and the MCP server exist. The user chose option 2, which pulls `feat/mcp-adapter` ahead of `feat/theme-scaffold` and `chore/testing-ci`.
