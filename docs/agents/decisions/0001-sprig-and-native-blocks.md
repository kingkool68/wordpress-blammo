# 0001 — Sprig (Twig) templating with native blocks; no ACF

- Status: accepted (2026-10-18)

## Context
The RH starter theme renders through Sprig (Twig) and builds blocks with ACF Pro (`acf_register_block_type` plus local field groups). Blammo should keep the starter's PHP → Twig separation but avoid a paid plugin dependency, and stay aligned with core block APIs.

## Decision
- Keep Sprig for all rendering. PHP builds `$context` and Twig renders it.
- Sprig is installed as an **mu-plugin** by `bin/install-mu-plugins`, because Sprig ships no release zips and needs `composer install` to vendor Twig. The same script installs [wp-styleguide](https://github.com/kingkool68/wp-styleguide), which routes `/styleguide/`.
- Blocks are **native** and dynamic:
  - `block.json` holds the metadata;
  - the JS edit component builds the editor UI;
  - a PHP `render_callback` calls the block class's `static render()`, which calls `Sprig::render()`.
- ACF isn't used.

## Consequences
- Block attributes live in `block.json`, and editor UI is written in React (`@wordpress/components`).
- `render()` stays callable from templates and the styleguide, independent of the editor.
- The theme has to handle Sprig being absent gracefully (`Blammo_Dependencies` returns early and serves a fallback template).
- The starter's ACF usage outside blocks changes too:
  - `get_field()` calls (e.g. `page_hide_title`) become `register_post_meta()` plus an editor panel;
  - the styleguide block-audit and block demos are rewritten for `blammo/*` blocks.

## Alternatives considered
- **ACF Pro blocks, as in the starter:** rejected because of the paid dependency and divergence from core APIs.
- **Timber instead of Sprig:** rejected; it's heavier, and the user's own Sprig matches the starter's templates.
- **Composer-installing Sprig/Twig into the theme's `vendor/`:** rejected so the theme has no runtime composer step and Sprig can be shared site-wide. It's an mu-plugin instead.
