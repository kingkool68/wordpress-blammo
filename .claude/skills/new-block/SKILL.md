---
name: new-block
description: Scaffold a new native Blammo block (block.json with render, edit JS, render.php, SCSS, styleguide entry, test stubs) following docs/agents/blocks.md. Args - block slug and a one-line description.
---

# new-block

Read `docs/agents/blocks.md` and `docs/agents/conventions.md` first. Then, given a `<slug>` (kebab-case) and a description:

1. **Check:**
   - `src/blocks/<slug>/` doesn't already exist, and the slug isn't a core block name;
   - a core block, pattern or block style variation can't do the job (blocks.md, "When to build a block").
2. **Create `src/blocks/<slug>/`:**
   - `block.json`:
     - `apiVersion: 3`, `name: "blammo/<slug>"`, `title`, `category: "blammo"`, `textdomain: "blammo"`, `description`;
     - typed `attributes` (with `default` values, and `enum` for anything that becomes a class name or tag);
     - `supports` may use `layout`, `color`, `spacing` and `typography`; prefer a support over a custom attribute;
     - `editorScript: "file:./index.js"`, `style: "file:./style-index.css"`, `render: "file:./render.php"`, and `editorStyle` / `viewScript` only if needed.
   - `index.js`: `registerBlockType( metadata.name, { edit: Edit, save: () => null } )`, importing `./style.scss`.
   - `edit.js`:
     - `useBlockProps`, plus `InspectorControls` with labelled controls;
     - a preview via `@wordpress/server-side-render` (`block="blammo/<slug>"`) unless an editable `RichText` UI is required.
   - `render.php`:
     - the root is `<div <?php echo get_block_wrapper_attributes( array( 'class' => '<slug>-block' ) ); ?>>`;
     - **every value escaped**, with an early `return` when there's nothing to render;
     - **no function, class or constant declarations**: helpers go in `functions/class-blammo-*.php`.
   - `style.scss`: BEM classes rooted at `.<slug>-block`, using `var(--wp--preset--*)` tokens from `theme.json`.
3. **Styleguide:** add `styleguide/<slug>.php`, served at `/styleguide/<slug>/` by the optional wp-styleguide plugin. Use `Blammo_Styleguide::page()` with `Blammo_Styleguide::block( 'blammo/<slug>', $attributes )`, using representative attributes plus an empty or edge case.
4. **Tests:**
   - `tests/phpunit/integration/test-blammo-<slug>-block.php`: registered, renders, escapes hostile input, handles empty attributes, two instances on one page render without a fatal;
   - `tests/e2e/<slug>-block.spec.js`: insert in the editor, publish, check the front end, and run an `@a11y` axe scan.
5. **Build and verify:** `npm run build`, then the checks in `docs/agents/testing.md`.
6. Add the block to `docs/agents/status.md`.

Registration is automatic: `Blammo_Blocks` registers everything in `build/blocks-manifest.php`, so run `npm run build`.
