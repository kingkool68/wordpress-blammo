# Architecture

> Status: this describes the **target** architecture. The scaffold lands in `feat/theme-scaffold`. See `status.md`.

## Directory map
```
functions.php                 # loader only (see Boot sequence)
functions/class-blammo-*.php  # one class per file; each self-bootstraps
src/blocks/<name>/            # one native block per dir (see blocks.md); wp-scripts auto-discovers block.json here
src/scss/{base,components}/   # theme styles; blammo.scss is the entry
src/js/                       # theme front-end (blammo.js) and editor (editor.js) entries
twig/                         # page-level Twig templates (base.twig is the HTML shell)
styleguide/                   # component demo pages (styleguide/<page>.php), served at /styleguide/<page>/ by the wp-styleguide mu-plugin
templates/fallback.php        # plain-PHP template used when Sprig is unavailable
build/                        # wp-scripts output (gitignored): build/blocks/<name>/, build/blammo.*, build/editor.*
tests/                        # phpunit/{unit,integration}, js, e2e
bin/                          # wp-local, install-mu-plugins, helper scripts
*.php (root)                  # WP template hierarchy files: build $context, call Sprig::out()
```

## Boot sequence (`functions.php`)
1. If `vendor/autoload.php` exists, require it. It holds dev tooling only; the theme has no runtime composer deps.
2. Require `functions/class-blammo-dependencies.php`. If `Blammo_Dependencies::is_sprig_ready()` is false, **return now**. That class then:
   - shows an admin notice;
   - filters `template_include` to `templates/fallback.php`;
   - loads no other classes or blocks, so nothing can call `Sprig::` and fatal.
   `is_sprig_ready()` checks both `class_exists( 'Sprig' )` and `class_exists( '\Twig\Environment' )`. Sprig's loader can succeed on any autoloader even when its Twig vendor dir is missing.
3. `require_once` an explicit list of `functions/class-blammo-*.php`:
   - `class-blammo-staging.php` only when `wp_get_environment_type() !== 'production'`;
   - `class-blammo-abilities.php` only when it is `local`.
4. Recursively require `src/blocks/**/*.php`.
5. Register Sprig filters:
   - `sprig/roots` adds `styleguide/` and every `src/blocks/*/` dir;
   - `sprig/twig/filters` adds `sanitize_title` and **`wp_kses_post`**;
   - `sprig/twig/functions` adds `get_language_attributes`.

## Class pattern
```php
class Blammo_Example {
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
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
		add_filter( 'body_class', array( $this, 'filter_body_class' ) );
	}

	public function action_init() {}

	public function filter_body_class( $classes = array() ) {
		return $classes;
	}

	public static function render( $args = array() ) {
		$defaults = array();
		$context  = wp_parse_args( $args, $defaults );
		return Sprig::render( 'example.twig', $context );
	}
}
Blammo_Example::get_instance();
```
- Hook callbacks are public instance methods named `action_<hook>` or `filter_<hook>`.
- Render and helper methods are `public static`, called as `Blammo_SVG::get_icon( 'close' )`.
- Utility-only classes have `get_instance()` without the setup calls.

## Render flow
```
WP template hierarchy → page.php
  → builds $context (titles, content, Sprig::do_action() captures for hook output)
  → Sprig::out( 'page.twig', $context )
      → page.twig {% extends 'base.twig' %} → wp_head(), body_class(), get_header(), get_footer()
```
- PHP owns the data and Twig only renders it, so keep logic out of Twig.
- Components and blocks expose `static render( $args )` and return a string.
- Templates may short-circuit with filters such as `blammo/header/show_header`.

## Sprig API and Twig details
- `Sprig::render( $tpl, $ctx )` returns a string, and `Sprig::out()` echoes it.
- `Sprig::do_action( $hook )` captures a hook's output as a string.
- **Sprig builds Twig on `init` priority 10.** Calling `Sprig::render()` earlier fatals, so never render in `after_setup_theme` or `init` < 10.
- **Search paths, in order:** the theme root, `twig/`, `views/`, then the `sprig/roots` additions (`styleguide/`, then each block dir).
  - Template names share **one flat namespace**, and the first match wins.
  - So block templates are named `<name>-block.twig`, and every template name must be unique across all of these dirs. A block template named `pagination.twig` would silently render `twig/pagination.twig`.
- **Autoescape is OFF** (ADR 0007). The available escaping filters are:
  - registered by Sprig: `esc_html`, `esc_attr`, `esc_url`, `esc_js`, `esc_textarea`, `tag_escape`, `sanitize_html_class`, `sanitize_email`, `antispambot`, `absint`, `wptexturize`;
  - registered by the theme: `wp_kses_post`, `sanitize_title`.
  Any other WP function used as a filter must be registered first, or Twig throws "Unknown filter" (a fatal error).

## Runtime dependencies (mu-plugins)
Both are kingkool68 plugins without release zips. `bin/install-mu-plugins <mu-plugins dir>`:
- clones both plugins;
- runs `composer install` for Sprig, to vendor Twig, and verifies `sprig/vendor/twig` exists;
- writes a `blammo-mu-loader.php` shim, because WordPress only auto-loads top-level files in `mu-plugins/`.

Mu-plugins load before the theme, so the theme's Sprig filters are registered in time.

| Plugin | Provides | Theme must cope with it missing |
|---|---|---|
| [Sprig](https://github.com/kingkool68/sprig) | `Sprig` class, Twig rendering | Yes: the `Blammo_Dependencies` early return plus a fallback template |
| [wp-styleguide](https://github.com/kingkool68/wp-styleguide) | Routes `/styleguide/<path>/` to `<theme>/styleguide/<path>.php`; `WP_Styleguide::get_sass_colors()` | Yes: it's a dev tool, and without the plugin the styleguide simply 404s |

- Styleguide pages are plain PHP files that call component `render()` methods. Every component and block gets one.
- **Known plugin issue:** `styleguide_path` is a public query var that reaches `locate_template()` unsanitized. Until the upstream fix is released, the theme strips everything except `[a-z0-9/_-]` and `..` segments from it (via a `request` filter).

## Assets
- `webpack.config.js` exports the wp-scripts default config, extended with the theme entries:
  - blocks are auto-discovered from `src/blocks/**/block.json` and built to `build/blocks/<name>/` (block.json is copied there);
  - the extra entries `blammo` (front-end JS + SCSS) and `editor` are merged with `...defaultConfig.entry()`, because `entry` is a function.
- `Blammo_Scripts_And_Styles` registers handles from `build/*.asset.php` (deps and version), with handle prefix `blammo-`.
- Editor canvas CSS uses `add_editor_style( 'build/editor.css' )`. The canvas is an iframe, so `enqueue_block_editor_assets` is used for editor JS only.
- Front-end block library CSS and global styles are dequeued, as in the starter. The `wp_render_layout_support_flag` and `wp_render_elements_support` filters are removed, so blocks must not declare `supports.layout` or color element supports.

## Not ported from the starter
- Gulp and `filter_cache_busting_file_src`: `*.asset.php` versions replace mtime busting.
- jQuery dependencies.
- ACF:
  - `get_field()` calls become `register_post_meta()` plus editor panels;
  - `RH_Blocks::get_acf_fields_for_block()` and the styleguide block-audit page are rewritten for `blammo/*` blocks.
- Deferred to backlog issues: CDN, Slack, Share_Tools, Redirects, Breadcrumbs, Post_Type_Archives.
- New in Blammo (not in the starter): `Blammo_Dependencies` and `Blammo_Abilities`.

## Deploy
- `deploy.yml` builds on pushes to `main` and commits the compiled theme to an orphan `build/main` branch, with dev files excluded (`.gitignore-build`). Production installs from `build/main`.
- **The main checkout that blammo.local renders has no `build/`** until you run `npm run build` after pulling `main`.
