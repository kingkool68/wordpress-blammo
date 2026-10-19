# 0008 — Full block (FSE) theme; `render.php` blocks; no Sprig/Twig

- Status: accepted (2026-10-18)
- Supersedes: 0001, 0003, 0007

## Context
The plan was a classic theme on the RH starter's conventions: Sprig (Twig) rendering, root PHP template files, SCSS-owned tokens and no `theme.json` (0001, 0003, 0007). No theme code exists yet. The user has changed direction: "Let's build a full block theme to see how that works. No more hybrid theme."

## Decision
- **Blammo is a block theme.** `templates/index.html` exists, so `wp_is_block_theme()` is true and core renders every request through `template-canvas.php`.
  - Templates: `templates/*.html`, block markup only, following the template hierarchy (`index`, `single`, `page`, `archive`, `search`, `404`, ...).
  - Template parts: `parts/*.html`, with areas declared in `theme.json` `templateParts` (`header`, `footer`).
  - Patterns: `patterns/*.php`, auto-registered from their file headers (`Title`, `Slug: blammo/<name>`, `Categories`, ...).
  - Optional style variations in `styles/*.json`.
  - No root PHP template files (`index.php`, `page.php`, ...).
- **`theme.json` (schema v3) is the single source of design tokens:** palette, typography (families, sizes, `fontFace`), spacing, layout (`contentSize` / `wideSize`), plus `settings.custom` for anything else. SCSS reads tokens through `--wp--preset--*` / `--wp--custom--*` custom properties and never redefines them.
- **Core CSS is no longer stripped.** Global styles, per-block library CSS (loaded on demand, which core turns on for block themes) and layout/element supports all stay on. Blocks may declare `supports.layout`, color, spacing and typography.
- **No Sprig, no Twig.** No mu-plugin, no `Blammo_Dependencies`, no fallback template, no `sprig/*` filters.
- **Dynamic blocks use core's `render.php`:** `block.json` has `"render": "file:./render.php"`. wp-scripts copies it to `build/blocks/<name>/` (`--webpack-copy-php`), and core `require`s it per render with `$attributes`, `$content` and `$block` in scope. It escapes at output and uses `get_block_wrapper_attributes()`. No per-block PHP class.
- **Remaining PHP keeps the `Blammo_*` singleton pattern** (block registration, scripts and styles, styleguide, SVG, staging, abilities). `functions.php` stays a loader.
- **Still no ACF.** Per-page switches the starter kept in ACF fields become a template choice where possible (e.g. a "No title" custom template), otherwise `register_post_meta()` plus an editor panel.
- **wp-styleguide stays** as an optional dev plugin (a regular plugin, no longer an mu-plugin, no install script). Its pages are plain PHP and print their own HTML shell: they render the theme's `header`/`footer` parts and the demo blocks with `do_blocks()` / `render_block()` **before** `wp_head()`, then print the page (architecture.md). The permanent `styleguide_path` `request` guard and its PHPUnit coverage stay.
- 0002 (wp-scripts), 0004 (wp-env), 0005 (agent workflow) and 0006 (MCP Adapter) stand, with small edits.

## Consequences
- The site editor can change templates, parts and global styles. **Edits are stored in the database** (`wp_template`, `wp_template_part`, `wp_global_styles` posts) and override the theme files on that site, including after a deploy. Agents check for and reset customizations before judging a file change (local-environment.md).
- Templates are static block markup. Anything that needs PHP (queries, computed values, translated strings) is a block with `render.php` or a pattern. Translatable template text lives in patterns and is referenced with `<!-- wp:pattern {"slug":"blammo/<name>"} /-->`.
- The starter's output-level control goes away: core decides much of the markup, class names and inline styles for core blocks. Styling core blocks means `theme.json` `styles.blocks` first, then `wp_enqueue_block_style()` overrides.
- The editor canvas matches the front end largely for free: `theme.json` styles, block `style`/`editorStyle` handles and fonts all reach the iframe.
- `render.php` is `require`d on every render, so it can't declare functions or classes (a second render fatals with "cannot redeclare"). Helpers live in `functions/class-blammo-*.php`.
- `src/` is no longer needed at runtime: all PHP blocks need is in `build/`. Deploy ships `build/`, `theme.json`, `templates/`, `parts/`, `patterns/`, `styles/`, `assets/` and the PHP.
- Activating Blammo on blammo.local swaps the site's chrome: classic menus, widgets and Customizer settings don't carry over. Navigation becomes `core/navigation` (`wp_navigation` posts).
- **Lost from the RH starter conventions:**
  - PHP builds `$context` and Twig renders it; `Sprig::do_action()` hook capture; `static render( $args )` methods callable from anywhere;
  - root PHP template files and `get_header()` / `get_footer()`;
  - `register_nav_menus()` plus custom nav markup; sidebars and widgets; Customizer settings;
  - template short-circuit filters like `blammo/header/show_header` (replaced by choosing a different template or part);
  - lean front end with core block CSS and global styles dequeued;
  - SCSS as the token source (`_colors`, `_fonts`); `WP_Styleguide::get_sass_colors()` (the colour page reads `wp_get_global_settings()` instead).

## Alternatives considered
- **Hybrid (classic theme, Sprig/Twig, native blocks, no `theme.json`): the previous plan (0001/0003/0007).** Rejected by the user in favour of trying full FSE.
- **Block theme, but Sprig/Twig for block rendering only:** rejected by the user. It keeps an mu-plugin dependency, the Sprig-missing failure mode and the autoescape-off XSS risk (0007), just to template a few blocks.
- **Hybrid "universal" theme (block templates plus PHP fallbacks):** rejected; two render paths to maintain and test.
