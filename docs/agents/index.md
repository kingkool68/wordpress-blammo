# Agent docs index

Read `status.md` first. After that, read only what your task needs.

| Doc | What it covers | Read when |
|---|---|---|
| [status.md](status.md) | What exists, what's in flight, what's next, known gaps | Always, first |
| [workflow.md](workflow.md) | Branch → worktree → implement → test → review → prompt artifact → PR | Before starting any task |
| [architecture.md](architecture.md) | Directory map, loader, class pattern, block-template render flow, `theme.json` tokens and what CSS reaches the editor canvas, styleguide, deploy | Adding or changing PHP, templates, parts, `theme.json`, assets or structure |
| [conventions.md](conventions.md) | Naming, PHP, template/part/pattern, SCSS and JS style, escaping, hooks | Writing any code |
| [blocks.md](blocks.md) | Anatomy of a `render.php` dynamic block and how to add one | Building or changing blocks |
| [testing.md](testing.md) | Test types, locations, commands, what each PR must include | Writing code or tests |
| [local-environment.md](local-environment.md) | Local by Flywheel site, `bin/wp-local`, wp-env, MCP Adapter | Touching the local site, the test env or MCP |
| [checklists/review.md](checklists/review.md) | General review checklist and finding format | Reviewing |
| [checklists/security.md](checklists/security.md) | WordPress security checklist | Reviewing or writing PHP (`render.php`, patterns, classes) |
| [checklists/accessibility.md](checklists/accessibility.md) | WCAG 2.2 AA checklist | Reviewing or writing markup, CSS or JS |
| [decisions/](decisions/) | ADRs: why things are the way they are | Before proposing a structural change |

## ADRs
| # | Decision |
|---|---|
| [0001](decisions/0001-sprig-and-native-blocks.md) | ~~Sprig (Twig) templating with native blocks, no ACF~~ (superseded by 0008) |
| [0002](decisions/0002-wp-scripts-build.md) | `@wordpress/scripts` is the only build tool |
| [0003](decisions/0003-no-theme-json.md) | ~~No `theme.json`; SCSS owns design tokens~~ (superseded by 0008) |
| [0004](decisions/0004-wp-env-testing.md) | All automated tests run on `@wordpress/env` |
| [0005](decisions/0005-agent-workflow.md) | Local reviewer agents and committed prompt artifacts on every PR |
| [0006](decisions/0006-mcp-adapter.md) | WordPress MCP Adapter over WP-CLI STDIO for local site access |
| [0007](decisions/0007-twig-autoescape-off.md) | ~~Keep Twig autoescape off (Sprig default); escape explicitly~~ (superseded by 0008) |
| [0008](decisions/0008-full-block-theme.md) | Full block (FSE) theme: `theme.json` tokens, `render.php` blocks, no Sprig/Twig, no ACF |
