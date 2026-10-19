# Native blocks

> Use the `new-block` skill to scaffold. This doc explains what it creates.

## Anatomy: `src/blocks/<name>/`
| File | Purpose |
|---|---|
| `block.json` | Metadata. See the required fields below. **No `render` key**: the PHP class supplies the `render_callback`. |
| `index.js` | `registerBlockType( metadata.name, { edit: Edit, save: () => null } )`. Imports `./style.scss`. It's a dynamic block, so `save` returns null. |
| `edit.js` | Edit component. It **must** spread `useBlockProps()` on its wrapper. It renders `InspectorControls` with labelled controls, plus `ServerSideRender` or a JS preview. |
| `style.scss` | Front-end and editor styles. wp-scripts emits them as `style-index.css`. |
| `view.js` | Optional front-end behaviour |
| `class-blammo-<name>-block.php` | Singleton that registers the block from `build/blocks/<name>` with a `render_callback` (see below) |
| `<name>-block.twig` | Markup. The `-block` suffix is required because Twig template names are global (architecture.md). Every dynamic value is escaped. |

### `block.json` required fields
- `apiVersion: 3`, `name: "blammo/<name>"`, `category: "blammo"`
- `editorScript: "file:./index.js"`, `style: "file:./style-index.css"`, plus `viewScript: "file:./view.js"` only when one exists
- Don't declare `supports.layout` or color element supports: the theme removes their render filters.

wp-scripts copies `block.json` to `build/blocks/<name>/`, so registration always points at `build/`, never `src/`.

## PHP class shape
```php
class Blammo_Example_Block {
	public static function get_instance() { /* singleton + setup_actions */ }

	public function action_init() {
		register_block_type(
			get_template_directory() . '/build/blocks/example',
			array( 'render_callback' => array( $this, 'render_from_block' ) )
		);
	}

	public static function render( $args = array() ) {
		$defaults = array(
			'headline'           => '',
			'wrapper_attributes' => 'class="example-block"', // pre-escaped attribute string
		);
		$context  = wp_parse_args( $args, $defaults );
		return Sprig::render( 'example-block.twig', $context );
	}

	public function render_from_block( $attributes = array(), $content = '', $block = null ) {
		return static::render(
			array(
				'headline'           => $attributes['headline'] ?? '',
				'wrapper_attributes' => get_block_wrapper_attributes( array( 'class' => 'example-block' ) ),
			)
		);
	}
}
Blammo_Example_Block::get_instance();
```
```twig
<div {{ wrapper_attributes }}>  {# pre-escaped by get_block_wrapper_attributes() #}
	<h2 class="example-block__headline">{{ headline|esc_html }}</h2>
</div>
```
- `render()` stays usable outside the editor, from templates and the styleguide.
- `render_from_block()` only maps attributes and wrapper attributes onto `render()`.
- `get_block_wrapper_attributes()` only works during a block render, which is why it lives in `render_from_block()`. Leaving it out silently drops the editor's Additional CSS class, anchor and align.

## Checklist for a new block
- [ ] `block.json` is valid and has the required fields above.
- [ ] It's dynamic (`save` returns `null`), and `edit` uses `useBlockProps()`.
- [ ] The wrapper attributes come from `get_block_wrapper_attributes()`.
- [ ] The Twig file is named `<name>-block.twig`, and every value is escaped.
- [ ] There's a styleguide page, `styleguide/<name>.php` (served at `/styleguide/<name>/`).
- [ ] Tests:
  - PHPUnit: the block is registered, render escapes hostile input, and empty attributes work.
  - Playwright: it inserts in the editor, renders on the front end, and passes an `@a11y` axe scan.
- [ ] `status.md` is updated.
