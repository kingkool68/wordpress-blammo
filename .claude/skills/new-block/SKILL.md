---
name: new-block
description: Scaffold a new native Blammo block (block.json, edit JS, PHP class with render()/render_callback, Twig, SCSS, styleguide entry, test stubs) following docs/agents/blocks.md. Args - block slug and a one-line description.
---

# new-block

Read `docs/agents/blocks.md` and `docs/agents/conventions.md` first. Then, given a `<slug>` (kebab-case) and a description:

1. **Check:** `src/blocks/<slug>/` doesn't already exist, and the slug isn't a core block name.
2. **Create `src/blocks/<slug>/`:**
   - `block.json`:
     - `apiVersion: 3`, `name: "blammo/<slug>"`, `title`, `category: "blammo"`, `description`;
     - typed `attributes` (with `default` values), `supports` (never `supports.layout` or color element supports: the theme removes their render filters);
     - `editorScript: "file:./index.js"`, `style: "file:./style-index.css"`, and `viewScript` only if needed.
   - `index.js`: `registerBlockType( metadata.name, { edit: Edit, save: () => null } )`, importing `./style.scss`.
   - `edit.js`:
     - `useBlockProps`, plus `InspectorControls` with labelled controls;
     - a preview via `@wordpress/server-side-render` (`block="blammo/<slug>"`) unless an editable `RichText` UI is required.
   - `style.scss`: BEM classes rooted at `.<slug>-block`, using tokens from `src/scss/base`.
   - `class-blammo-<slug>-block.php`: the singleton with `action_init` → `register_block_type( get_template_directory() . '/build/blocks/<slug>', [ 'render_callback' => … ] )`, plus `static render( $args )` and `render_from_block( $attributes, $content, $block )`. `render_from_block` passes `get_block_wrapper_attributes()` in as `wrapper_attributes`.
   - `<slug>-block.twig`: markup with **every value escaped**. The `-block` suffix is required because template names are global. The root element is `<div {{ wrapper_attributes }}>`.
3. **Styleguide:** add `styleguide/<slug>.php`, served at `/styleguide/<slug>/` by the wp-styleguide mu-plugin. It should call `Blammo_<Name>_Block::render()` with representative args, including an empty or edge case.
4. **Tests:**
   - `tests/phpunit/integration/test-blammo-<slug>-block.php`: registered, renders, escapes hostile input, handles empty attributes;
   - `tests/e2e/<slug>-block.spec.js`: insert in the editor, publish, check the front end, and run an `@a11y` axe scan.
5. **Build and verify:** `npm run build`, then the checks in `docs/agents/testing.md`.
6. Add the block to `docs/agents/status.md`.

The block class is loaded automatically: `functions.php` requires `src/blocks/**/*.php`.
