# Architecture

> Status: the scaffold landed in `feat/theme-scaffold`. Abilities, `bin/` and CI are still pending (see `status.md`). Blammo is a full block (FSE) theme (ADR 0008).

## Directory map
```
style.css                     # theme header (Name, Version, Requires at least: 7.0, Text Domain: blammo); no real CSS
theme.json                    # schema v3; the single source of design tokens and global styles
functions.php                 # loader only (see Boot sequence)
functions/class-blammo-*.php  # one class per file; each self-bootstraps
templates/*.html              # block templates (template hierarchy); templates/index.html makes this a block theme
parts/*.html                  # template parts (header.html, footer.html, ...); areas declared in theme.json templateParts
patterns/*.php                # block patterns, auto-registered from file headers (Slug: blammo/<name>)
styles/*.json                 # optional style variations
assets/{fonts,images}/        # static runtime assets (fonts referenced by theme.json fontFace)
src/blocks/<name>/            # one dynamic block per dir (see blocks.md); wp-scripts auto-discovers block.json here
src/scss/                     # blammo.scss (front end + canvas), editor.scss (canvas-only tweaks); base/ holds mixins, functions, breakpoints
src/js/                       # front-end (blammo.js) and editor (editor.js) entries
build/                        # wp-scripts output (gitignored): build/blocks/<name>/ (block.json, render.php, assets), build/blocks-manifest.php, build/blammo.*, build/editor.*
styleguide/                   # component demo pages (styleguide/<page>.php), served at /styleguide/<page>/ by the optional wp-styleguide plugin
tests/                        # phpunit/{unit,integration}, js, e2e
bin/                          # wp-local, helper scripts
```
- There are **no root PHP template files** (`index.php`, `page.php`, ...). Core serves every request through `wp-includes/template-canvas.php`.
- `src/` is build input only. Nothing under it is loaded at runtime.

## Boot sequence (`functions.php`)
1. `require_once` an explicit list of `functions/class-blammo-*.php`:
   - `class-blammo-blocks.php`, `class-blammo-scripts-and-styles.php`, `class-blammo-styleguide.php`, `class-blammo-svg.php`;
   - `class-blammo-staging.php` only when `wp_get_environment_type() !== 'production'`;
   - `class-blammo-abilities.php` only when it is `local`.
2. That's all. No composer autoload (the theme has no runtime composer deps), and **never require anything under `src/blocks/`**: `render.php` files print output and are only loaded by core during a block render.

## Class pattern
```php
class Blammo_Blocks {
	public static function get_instance() {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new static();
			$instance->setup_actions();
			$instance->setup_filters();
		}
		return $instance;
	}

	public function setup_actions() {
		add_action( 'init', array( $this, 'action_init' ) );
	}

	public function setup_filters() {
		add_filter( 'block_categories_all', array( $this, 'filter_block_categories_all' ) );
	}

	public function action_init() {
		register_block_pattern_category( 'blammo', array( 'label' => __( 'Blammo', 'blammo' ) ) );

		$manifest = get_template_directory() . '/build/blocks-manifest.php';
		if ( ! file_exists( $manifest ) ) {
			return; // build/ missing: no blocks, no fatal.
		}
		wp_register_block_types_from_metadata_collection( get_template_directory() . '/build/blocks', $manifest );
	}

	public function filter_block_categories_all( $categories = array() ) {
		$categories[] = array(
			'slug'  => 'blammo',
			'title' => __( 'Blammo', 'blammo' ),
		);
		return $categories;
	}
}
Blammo_Blocks::get_instance();
```
- Hook callbacks are public instance methods named `action_<hook>` or `filter_<hook>`.
- Helpers are `public static` and **return** strings (`Blammo_SVG::get_icon( 'close' )`), so `render.php` and patterns can call them.
- Utility-only classes have `get_instance()` without the setup calls.
- Block-specific logic too big for `render.php` goes in a `functions/` class, never in `render.php` (it can't declare functions; see blocks.md).

## Render flow
```
request → template hierarchy (page-{slug} → page-{id} → page → singular → index)
  → locate_block_template(): DB-customized wp_template post, else templates/<slug>.html
  → template-canvas.php
      → get_the_block_template_html(): do_blocks( template markup )      ← runs BEFORE wp_head()
          → core/template-part → parts/header.html (or its DB override) → <header>
          → core/post-content, core/query, ... (core render callbacks)
          → core/pattern → patterns/<name>.php
          → blammo/<name> → build/blocks/<name>/render.php ($attributes, $content, $block)
          → skip link inserted before div.wp-site-blocks, targeting the first <main> (core adds an id if it has none)
      → <html language_attributes()> … wp_head() … body_class() … wp_body_open() … template HTML … wp_footer()
```
- Templates and parts are static block markup: no PHP, no translatable strings. Put strings in patterns and dynamic data in blocks.
- Because blocks render before `wp_head()`, block styles enqueued during render print in the head.
- **Database overrides win.** Once a template, part or global style is edited in the site editor, its `wp_template` / `wp_template_part` / `wp_global_styles` post is used instead of the theme file on that site.
- Variants (e.g. no page title) are custom templates declared in `theme.json` `customTemplates`, not filters.

## Design tokens and assets
- **`theme.json` owns tokens:** `settings.color.palette`, `settings.typography.{fontFamilies,fontSizes}` (fonts self-hosted via `fontFace` → `file:./assets/fonts/...`), `settings.spacing.spacingSizes`, `settings.layout.{contentSize,wideSize}`, `settings.custom` for anything else. Core default palettes/sizes and free-form custom values are switched off where they'd bypass the tokens.
- `styles` in `theme.json` handles global element and core block styling first. SCSS is for what `theme.json` can't express: per-block styles, states, media queries, complex selectors.
- SCSS reads tokens as `var(--wp--preset--color--<slug>)`, `var(--wp--preset--spacing--<slug>)`, `var(--wp--custom--<path>)`. Breakpoints stay SCSS variables (custom properties don't work in media queries).
- `webpack.config.js` exports the wp-scripts default config, extended with the entries `blammo` and `editor`, merged with `...defaultConfig.entry()` (because `entry` is a function). Build with `--webpack-copy-php` (copies `render.php` into `build/blocks/<name>/`) and `--blocks-manifest` (writes `build/blocks-manifest.php`). Confirm both flags against the installed wp-scripts version; if the manifest flag is missing, register by globbing `build/blocks/*/block.json`.
- `Blammo_Scripts_And_Styles` registers handles from `build/*.asset.php` (deps and version), with handle prefix `blammo-`. Scripts are only registered when `build/<entry>.js` is non-empty (an entry may only import SCSS), and `blammo.css` uses `blammo-rtl.css` in RTL locales.

### What reaches what
| Asset | Front end | Editor canvas (iframe) | Editor UI (outer frame) |
|---|---|---|---|
| `theme.json` global styles and presets | yes (core) | yes (core) | no |
| `theme.json` `fontFace` fonts | yes | yes | no |
| Block `style` (`style-index.css`) | yes, only on pages that render the block | yes | no |
| Block `editorStyle` | no | yes | no |
| Block `viewScript` | yes, on demand | no | no |
| Block `editorScript` | no | no | yes |
| `wp_enqueue_block_style( 'core/<block>', … )` (core block overrides) | yes, on demand | yes | yes (in admin it hooks `enqueue_block_assets`), so scope selectors to the block's class |
| `build/blammo.css` via `wp_enqueue_scripts` | yes | no | no |
| `build/blammo.css` + `build/editor.css` via `add_editor_style()` (called in `action_after_setup_theme`) | no | yes (core scopes it to `.editor-styles-wrapper`) | no |
| `build/editor.js` via `enqueue_block_editor_assets` | no | no | yes |
- Rule: content CSS reaches the canvas through `theme.json`, block `style`/`editorStyle`, `wp_enqueue_block_style()` or `add_editor_style()`. Anything enqueued on `enqueue_block_editor_assets` stays in the outer frame, so use it for JS only.
- Avoid enqueueing theme CSS on `enqueue_block_assets`: it also loads into the outer editor frame and can restyle the admin UI.
- Block themes get `editor-styles`, `html5`, `post-thumbnails`, `responsive-embeds` and `automatic-feed-links` from core; don't re-add them.

## Styleguide (optional wp-styleguide plugin)
[wp-styleguide](https://github.com/kingkool68/wp-styleguide) filters `template_include` and serves `locate_template( 'styleguide/<path>.php' )` for the public query var `styleguide_path`. It replaces `template-canvas.php`, so a styleguide page is plain PHP that **prints the whole HTML document itself**.
- It's a regular dev plugin, installed by hand on blammo.local and from a pinned commit zip in `.wp-env.json` `plugins`. No mu-plugin, no install script. Without it, `/styleguide/` 404s and the theme is unaffected.
- `Blammo_Styleguide` provides the shell, so pages stay short:
  - `Blammo_Styleguide::block( 'blammo/<name>', $attributes, $inner_html = '' )` returns `render_block()` output for a parsed-block array with `innerHTML` **and** `innerContent => array( $inner_html )` (`WP_Block::render()` builds `$content` from `innerContent` only). `$inner_html` is rendered HTML; for inner *blocks*, build markup with `serialize_block()` and pass it through `do_blocks()`. Don't hand-build block-comment JSON from values.
  - `Blammo_Styleguide::page( $title, $body_html )` prints the document in the same order as `template-canvas.php`:
    1. render first: `do_blocks( '<!-- wp:template-part {"slug":"header","area":"header"} /-->' )`, the same for `footer`. Both this and `block_template_part()` honour DB overrides, but this returns a string and wraps the part in its area landmark (`<header>` / `<footer>`); `block_template_part()` echoes the raw part with no wrapper;
    2. then print `<!DOCTYPE html>`, `<html <?php language_attributes(); ?>>`, `<head>`, `<meta charset="<?php bloginfo( 'charset' ); ?>">`, `wp_head()`, `</head>`, `body_class()`, `wp_body_open()`. In block themes the viewport meta and `<title>` come from `wp_head` callbacks that `locate_block_template()` adds (`_block_template_viewport_meta_tag`, `_block_template_render_title_tag`), which may not have run on a styleguide request. `page()` adds them itself when `has_action()` shows they're missing, so they're never printed twice;
    3. `<a class="skip-link screen-reader-text" href="#main">` (core only injects its skip link into real block templates), then `<div class="wp-site-blocks">` containing the header part, a `<main id="main">` built with `do_blocks()` as a `core/group` (`"tagName":"main","layout":{"type":"constrained"}`) around `$body_html`, and the footer part. `theme.json` root padding, `blockGap` and the align rules target `.wp-site-blocks` and the constrained layout, so without them demos don't match the front end;
    4. `wp_footer()`.
  - wp-styleguide sets page titles through `wp_title`, which block themes never call. `Blammo_Styleguide` filters `document_title_parts` on styleguide requests.
  - Styleguide pages run at **global scope** (`template-loader.php` `include`s them), so their variables are prefixed `$blammo_`. Pages build their body with `ob_start()` and pass `ob_get_clean()` to `page()`; styleguide-only styling stays inline so it never ships in `build/blammo.css`.
  - Rendering before `wp_head()` lets on-demand block CSS print in the head instead of late in the footer.
- Pages may also pass literal core block markup through `do_blocks()`. The colour page reads `wp_get_global_settings( array( 'color', 'palette', 'theme' ) )`.
- Every block gets a page, `styleguide/<name>.php`, including an empty/edge case.
- **`styleguide_path` guard (kept permanently, even after the upstream fix):** `styleguide_path` is a public query var that reaches `locate_template()`. `Blammo_Styleguide` adds a `request` filter that:
  1. lowercases the value, then removes every character outside `[a-z0-9/_-]`, which drops all dots;
  2. unsets the var if the result is empty or starts with `/`.

  It needs PHPUnit coverage for `../`, `%2e%2e/` and `/etc/passwd`. It loads whether or not the plugin is active.

## Not ported from the starter
- Sprig/Twig, `$context` building, root PHP templates, `static render()` component methods (ADR 0008).
- Gulp and `filter_cache_busting_file_src`: `*.asset.php` versions replace mtime busting.
- Dequeuing global styles / block library CSS, and removing the layout and elements support filters.
- `register_nav_menus()`, nav walkers, sidebars/widgets, Customizer settings: replaced by `core/navigation`, template parts and global styles.
- jQuery dependencies.
- ACF:
  - per-page switches become custom templates, or `register_post_meta()` plus an editor panel;
  - `RH_Blocks::get_acf_fields_for_block()` and the styleguide block-audit page are rewritten for `blammo/*` blocks.
- Deferred to backlog issues: CDN, Slack, Share_Tools, Redirects, Breadcrumbs, Post_Type_Archives.
- New in Blammo (not in the starter): `Blammo_Blocks`, `Blammo_Styleguide`, `Blammo_Abilities`.

## Deploy
- `deploy.yml` builds on pushes to `main` and commits the compiled theme to an orphan `build/main` branch, with dev files excluded (`.gitignore-build`). Production installs from `build/main`.
- **Runtime files that must ship:** `style.css`, `theme.json`, `functions.php`, `functions/`, `templates/`, `parts/`, `patterns/`, `styles/`, `assets/` and **`build/`** (block `render.php` files and `blocks-manifest.php` live there). `src/`, `styleguide/`, `tests/`, `bin/` and tool configs are dev-only.
- **`deploy.yml` stamps `style.css` `Version`** (build date + short SHA) on every `build/main` commit. Core caches parsed `patterns/*.php` in the `wp_theme_files_patterns-<hash>` site transient and only re-reads them when `Version` changes (or `wp_is_development_mode( 'theme' )`). The cache holds only header fields and the file path (bodies are `include`d fresh), so without the bump new pattern files and header changes (`Slug`, `Title`, `Categories`, `Inserter`, …) don't appear, and templates referencing a new slug render nothing. Body edits show up immediately.
- A deploy doesn't touch site-editor customizations: a changed `templates/*.html` has no effect on a site where that template is customized until the customization is reset.
- **The main checkout that blammo.local renders has no `build/`** until you run `npm run build` after pulling `main`. Without it no `blammo/*` blocks register: the front end silently renders nothing for them, and the editor shows them as unsupported.
