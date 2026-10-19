# 0003 — No `theme.json`; SCSS owns design tokens

- Status: superseded by 0008 (2026-10-18)

## Context
`theme.json` would introduce a second source of design tokens and front-end global styles, which the starter deliberately dequeues.

## Decision
Blammo is a classic theme with no `theme.json`. Design tokens live in `src/scss/base/`.
- Editor canvas parity comes from `add_editor_style( 'build/editor.css' )`. The apiVersion 3 editor canvas is an iframe, and stylesheets enqueued on `enqueue_block_editor_assets` don't reach it.
- `enqueue_block_editor_assets` is used for editor JS only.

## Consequences
- Editor colour palette and font sizes, if wanted, are set with `add_theme_support( 'editor-color-palette', … )` in PHP, mirroring the SCSS tokens by hand.
- Revisit with a new ADR if block-editor styling needs grow.

## Alternatives considered
- **Minimal `theme.json` for editor settings only:** rejected for now, to keep one token source.
