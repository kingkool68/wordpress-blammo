---
date: 2026-10-18
branch: feat/abilities
session_id: 5282b926-4f55-4edf-b759-60c82f6a2363
---

# Prompts: abilities

Verbatim prompts that produced this change, in order.

## Prompt 1 (2026-10-19T01:58:59Z)

~~~~text
<ide_opened_file>The user opened the file ~/Sites/blammo.local/app/public/wp-content/themes/blammo/.mcp.json in the IDE. This may or may not be related to the current task.</ide_opened_file>
Ok make sure the MCP adapter is working
~~~~

## Prompt 2 (2026-10-19T01:59:54Z)

~~~~text
Add an ability to create a post
~~~~

## Prompt 3 (2026-10-19T02:25:32Z)

~~~~text
Theme scaffolding is done. I have switched the theme to the vlammo theme
~~~~

## Clarifications

- **Q:** Where should the create-post ability live, given Blammo isn't active on blammo.local yet? (Theme, stacked on `feat/theme-scaffold` / Theme, wait / Local plugin now)
  **A:** Theme, stacked (Recommended)
- **Q:** What post statuses may the ability create? (Draft only / Draft or pending / Any incl. publish)
  **A:** Any incl. publish
- **Q:** feat/theme-scaffold isn't pushed, so a PR could only target main and include the scaffold commits. How should I open it? (Hold until scaffold PR / PR against main now / Don't push yet)
  **A:** Hold until scaffold PR (Recommended). The scaffold then merged (#5), so this PR targets main.
