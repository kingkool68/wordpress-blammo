<?php
/**
 * Styleguide support for the optional wp-styleguide plugin
 *
 * wp-styleguide serves styleguide/<path>.php for /styleguide/<path>/ and replaces
 * template-canvas.php, so each page prints its own HTML document. page() builds
 * that document the same way core's block template canvas does. See
 * "Styleguide" in docs/agents/architecture.md.
 *
 * The styleguide_path guard runs whether or not the plugin is active, and stays
 * even though the plugin validates the path itself.
 */
class Blammo_Styleguide {

	/**
	 * Title of the styleguide page being rendered, set by page()
	 *
	 * @var string
	 */
	private static $page_title = '';

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new static();
			$instance->setup_filters();
		}
		return $instance;
	}

	/**
	 * Hook into WordPress filters
	 */
	public function setup_filters() {
		add_filter( 'request', array( $this, 'filter_request' ) );
		add_filter( 'document_title_parts', array( $this, 'filter_document_title_parts' ) );
	}

	/**
	 * Sanitize the public styleguide_path query var before it reaches locate_template()
	 *
	 * @param array $query_vars Parsed query vars
	 * @return array Query vars with styleguide_path sanitized, or removed if nothing safe is left
	 */
	public function filter_request( $query_vars = array() ) {
		if ( ! is_array( $query_vars ) || ! array_key_exists( 'styleguide_path', $query_vars ) ) {
			return $query_vars;
		}
		$path = static::sanitize_path( $query_vars['styleguide_path'] );
		if ( $path === '' ) {
			unset( $query_vars['styleguide_path'] );
		} else {
			$query_vars['styleguide_path'] = $path;
		}
		return $query_vars;
	}

	/**
	 * Reduce a styleguide path to lowercase a-z, 0-9, '/', '_' and '-'
	 *
	 * Every other character, including every dot, is removed. A result that
	 * starts with '/' is rejected, so '../x' and '/etc/passwd' can't escape the
	 * styleguide directory.
	 *
	 * @param mixed $path Raw styleguide_path value
	 * @return string Sanitized path, or an empty string if nothing safe is left
	 */
	public static function sanitize_path( $path = '' ) {
		if ( ! is_string( $path ) ) {
			return '';
		}
		$path = preg_replace( '/[^a-z0-9\/_-]/', '', strtolower( $path ) );
		if ( ! is_string( $path ) || $path === '' || str_starts_with( $path, '/' ) ) {
			return '';
		}
		return $path;
	}

	/**
	 * Whether the current request is for a styleguide page
	 *
	 * @return bool
	 */
	public static function is_styleguide_request() {
		return ! empty( get_query_var( 'styleguide' ) );
	}

	/**
	 * Set the document title on styleguide pages. wp-styleguide filters wp_title, which block themes never call.
	 *
	 * @param array $parts Document title parts
	 * @return array Modified title parts
	 */
	public function filter_document_title_parts( $parts = array() ) {
		if ( ! static::is_styleguide_request() ) {
			return $parts;
		}
		$styleguide_name = sprintf(
			/* translators: %s: Site name. */
			__( '%s Styleguide', 'blammo' ),
			get_bloginfo( 'name', 'display' )
		);

		// The index (no path) is titled with the styleguide name alone.
		$path  = static::sanitize_path( get_query_var( 'styleguide_path' ) );
		$title = '';
		if ( $path !== '' ) {
			$title = static::$page_title !== '' ? static::$page_title : ucwords( str_replace( array( '-', '_', '/' ), ' ', $path ) );
		}

		$parts = array();
		if ( $title === '' ) {
			$parts['title'] = $styleguide_name;
		} else {
			$parts['title'] = $title;
			$parts['site']  = $styleguide_name;
		}
		return $parts;
	}

	/**
	 * Render a block outside a template, the way core renders a parsed block
	 *
	 * @param string $block_name Block name, e.g. 'blammo/example'
	 * @param array  $attributes Block attributes
	 * @param string $inner_html Rendered inner HTML. For inner blocks, build markup with serialize_block() and pass it through do_blocks().
	 * @return string Rendered block HTML
	 */
	public static function block( $block_name = '', $attributes = array(), $inner_html = '' ) {
		if ( ! is_string( $block_name ) || $block_name === '' ) {
			return '';
		}
		$inner_html = (string) $inner_html;
		return render_block(
			array(
				'blockName'    => $block_name,
				'attrs'        => is_array( $attributes ) ? $attributes : array(),
				'innerBlocks'  => array(),
				'innerHTML'    => $inner_html,
				// WP_Block::render() builds $content from innerContent only.
				'innerContent' => array( $inner_html ),
			)
		);
	}

	/**
	 * Print a complete styleguide page in the same order as wp-includes/template-canvas.php
	 *
	 * Blocks render before wp_head() so styles they enqueue print in the head.
	 *
	 * @param string $title     Page title, shown as the <h1> and in the document title
	 * @param string $body_html Rendered, already-escaped HTML for the page body
	 */
	public static function page( $title = '', $body_html = '' ) {
		static::$page_title = is_string( $title ) ? trim( $title ) : '';

		// 1. Render first.
		$header  = do_blocks( '<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->' );
		$heading = '';
		if ( static::$page_title !== '' ) {
			$heading = '<h1 class="wp-block-heading">' . esc_html( static::$page_title ) . '</h1>';
		}
		$main   = static::block(
			'core/group',
			array(
				'tagName' => 'main',
				'layout'  => array( 'type' => 'constrained' ),
			),
			'<main id="main" class="wp-block-group">' . $heading . (string) $body_html . '</main>'
		);
		$footer = do_blocks( '<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"footer"} /-->' );

		// Block templates add these from locate_block_template(), which may not have run on a styleguide request.
		if ( ! has_action( 'wp_head', '_block_template_viewport_meta_tag' ) ) {
			add_action( 'wp_head', '_block_template_viewport_meta_tag', 0 );
		}
		if ( ! has_action( 'wp_head', '_block_template_render_title_tag' ) ) {
			add_action( 'wp_head', '_block_template_render_title_tag', 1 );
		}

		// 2. Then print the document.
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
		<?php wp_head(); ?>
</head>
<body <?php body_class( 'blammo-styleguide' ); ?>>
		<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'blammo' ); ?></a>
<div class="wp-site-blocks">
		<?php
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered block output and caller-escaped body HTML.
		echo $header;
		echo $main;
		echo $footer;
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
</div>
		<?php wp_footer(); ?>
</body>
</html>
		<?php
	}
}
Blammo_Styleguide::get_instance();
