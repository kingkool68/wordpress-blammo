# Native blocks

> Use the `new-block` skill to scaffold. This doc explains what it creates. Blocks are dynamic and render with core's `render.php` (ADR 0008).

## When to build a block
- Prefer core blocks, a pattern (`patterns/<name>.php`) or a block style variation first.
- Build a `blammo/*` block when the output needs PHP at render time (queries, computed values, icons) or an editor UI that core blocks can't give.

## Anatomy: `src/blocks/<name>/`
| File | Purpose |
|---|---|
| `block.json` | Metadata, including `"render": "file:./render.php"`. See the required fields below. |
| `index.js` | `registerBlockType( metadata.name, { edit: Edit, save } )`. Imports `./style.scss`. `save` returns `null`, or `<InnerBlocks.Content />` when the block has inner blocks. |
| `edit.js` | Edit component. It **must** spread `useBlockProps()` on its wrapper. It renders `InspectorControls` with labelled controls, plus a JS preview (e.g. `RichText`) or `ServerSideRender`. |
| `render.php` | Front-end markup. Core `require`s it on every render. Escapes every value. |
| `style.scss` | Front-end and editor styles. wp-scripts emits them as `style-index.css`. |
| `editor.scss` | Optional, editor-canvas-only styles (`editorStyle`). |
| `view.js` | Optional front-end behaviour. |

There's no per-block PHP class. `Blammo_Blocks` registers every block from `build/blocks-manifest.php` (architecture.md). wp-scripts copies `block.json` and `render.php` to `build/blocks/<name>/`, and core only ever loads them from `build/`.

### `block.json` required fields
- `apiVersion: 3`, `name: "blammo/<name>"`, `category: "blammo"`, `textdomain: "blammo"`
- `editorScript: "file:./index.js"`, `style: "file:./style-index.css"`, `render: "file:./render.php"`
- `editorStyle: "file:./index.css"` only when `editor.scss` exists; `viewScript: "file:./view.js"` only when `view.js` exists
- Typed `attributes` with `default`s. Core drops values that fail the type/enum check and falls back to the default, so use `enum` for anything that becomes a class name or tag.
- `supports` are allowed, including `layout`, `color`, `spacing` and `typography`. Prefer a support (which uses the `theme.json` presets) over a custom attribute for the same thing.
- `usesContext: [ "postId", "postType" ]` when the block reads the current post, so it works inside a Query Loop.

## `render.php`
```php
<?php
/**
 * @var array    $attributes Block attributes (defaults applied, invalid values dropped).
 * @var string   $content    Rendered inner blocks (already HTML).
 * @var WP_Block $block      Block instance; $block->context holds usesContext values.
 */

$headline = (string) ( $attributes['headline'] ?? '' );
if ( '' === trim( $headline ) ) {
	return; // Renders nothing.
}
?>
<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'example-block' ) ); ?>>
	<h2 class="example-block__headline"><?php echo esc_html( $headline ); ?></h2>
	<?php echo Blammo_SVG::get_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- returns escaped SVG. ?>
</div>
```
- **Escape at output:** `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`. Translated strings use `esc_html__( '…', 'blammo' )` / `esc_html_e()`.
- Echo as-is only values that are already safe HTML, and say why in an inline comment: `get_block_wrapper_attributes()` (escaped by core), `$content` (rendered inner blocks), theme helpers documented as returning escaped HTML (e.g. `Blammo_SVG::get_icon()`). Add a `phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` with that reason if PHPCS flags it.
- **Always use `get_block_wrapper_attributes()`** on the root element. Leaving it out silently drops the editor's Additional CSS class, anchor, align and every `supports` style. It adds `wp-block-blammo-<name>` itself; `<name>-block` is the BEM root for SCSS.
- **No function, class or constant declarations.** The file is `require`d on every render, so a second instance of the block fatals with "cannot redeclare". Put helpers in `functions/class-blammo-*.php`.
- `return` early to render nothing. Don't `echo` outside the markup and don't `exit`.
- Queries: `WP_Query` with `no_found_rows => true`, and `wp_reset_postdata()` after any loop that calls `the_post()`.
- Never `require` a `render.php` yourself, and never from `functions.php`.
- `render.php` runs inside a closure, so its variables aren't globals. `phpcs.xml.dist` excludes `src/blocks/*/render.php` and `patterns/` from `WordPress.NamingConventions.PrefixAllGlobals`. `styleguide/` is **not** excluded: wp-styleguide pages are `include`d from `template-loader.php` at global scope, so their variables are real globals and use the `$blammo_` prefix (an unprefixed `$pages` would clobber core's pagination global).

## Rendering outside a template
- Styleguide pages: `Blammo_Styleguide::block( 'blammo/<name>', $attributes )` (wraps `render_block()`).
- PHP code and tests: `render_block( array( 'blockName' => 'blammo/<name>', 'attrs' => $attributes, 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) )`, or `do_blocks()` with literal markup. Both need `build/` to exist.

## Checklist for a new block
- [ ] `block.json` is valid and has the required fields above, including `render`.
- [ ] It's dynamic (`save` returns `null` or only `<InnerBlocks.Content />`), and `edit` uses `useBlockProps()`.
- [ ] `render.php` uses `get_block_wrapper_attributes()`, escapes every value, declares nothing, and handles empty attributes.
- [ ] There's a styleguide page, `styleguide/<name>.php` (served at `/styleguide/<name>/`), including an empty or edge case.
- [ ] Tests:
  - PHPUnit: the block is registered, render escapes hostile input, empty attributes work, and two instances on one page render without a fatal.
  - Playwright: it inserts in the editor, renders on the front end, and passes an `@a11y` axe scan.
- [ ] `status.md` is updated.
