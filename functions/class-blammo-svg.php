<?php
/**
 * Helpers for rendering SVG icons inline
 *
 * Icons live in assets/icons/<name>.svg. They're theme files, so their markup is
 * trusted; only the attributes added here come from arguments, and those are escaped.
 */
class Blammo_SVG {

	/**
	 * Per-request cache of get_all_icons()
	 *
	 * @var array|null
	 */
	private static $all_icons_cache = null;

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new static();
		}
		return $instance;
	}

	/**
	 * Absolute path to the icons directory
	 *
	 * @return string
	 */
	public static function get_icons_path() {
		return get_template_directory() . '/assets/icons';
	}

	/**
	 * Get an icon from assets/icons/ as inline SVG markup
	 *
	 * Icons are decorative by default (aria-hidden). Pass a 'label' to expose
	 * the icon to assistive technology as an image with that name.
	 *
	 * @param string $icon Icon file name without the extension. Only a-z, 0-9, - and _ are allowed.
	 * @param array  $args {
	 *     Optional.
	 *
	 *     @type string|array $css_class     Replaces the default classes ('icon icon-<name>').
	 *     @type string|array $add_css_class Classes added to the default classes.
	 *     @type string       $label         Accessible name. Empty means decorative.
	 * }
	 * @return string Escaped inline SVG markup, or an empty string if the icon doesn't exist
	 */
	public static function get_icon( $icon = '', $args = array() ) {
		if ( ! is_string( $icon ) || ! preg_match( '/^[a-z0-9_-]+$/', $icon ) ) {
			return '';
		}
		$defaults = array(
			'css_class' => 'icon icon-' . $icon,
		);
		$args     = wp_parse_args( $args, $defaults );
		return static::get_svg( static::get_icons_path() . '/' . $icon . '.svg', $args );
	}

	/**
	 * Read an SVG file and add class and accessibility attributes to its root element
	 *
	 * @param string $path Absolute path to an SVG file
	 * @param array  $args See get_icon()
	 * @return string Inline SVG markup, or an empty string if the file doesn't exist
	 */
	public static function get_svg( $path = '', $args = array() ) {
		if ( ! is_string( $path ) || $path === '' || ! is_file( $path ) ) {
			return '';
		}
		$args = wp_parse_args(
			$args,
			array(
				'css_class'     => '',
				'add_css_class' => '',
				'label'         => '',
			)
		);

		$classes = array_merge(
			static::to_class_list( $args['css_class'] ),
			static::to_class_list( $args['add_css_class'] )
		);
		$classes = array_values( array_unique( $classes ) );

		$attributes = '';
		if ( ! empty( $classes ) ) {
			$attributes .= ' class="' . esc_attr( implode( ' ', $classes ) ) . '"';
		}
		$label = is_string( $args['label'] ) ? trim( $args['label'] ) : '';
		if ( $label !== '' ) {
			$attributes .= ' role="img" aria-label="' . esc_attr( $label ) . '"';
		} else {
			$attributes .= ' aria-hidden="true"';
		}
		$attributes .= ' focusable="false"';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
		$svg = file_get_contents( $path );
		if ( ! is_string( $svg ) ) {
			return '';
		}
		return trim( (string) preg_replace( '/<svg\b/', '<svg' . $attributes, $svg, 1 ) );
	}

	/**
	 * Get every icon in assets/icons/
	 *
	 * @return array Objects with 'label', 'svg' and 'url', keyed and sorted by icon name
	 */
	public static function get_all_icons() {
		if ( static::$all_icons_cache !== null ) {
			return static::$all_icons_cache;
		}
		$icons = array();
		$files = glob( static::get_icons_path() . '/*.svg' );
		foreach ( (array) $files as $file ) {
			$name = basename( $file, '.svg' );
			$svg  = static::get_icon( $name );
			if ( $svg === '' ) {
				continue;
			}
			$icons[ $name ] = (object) array(
				'label' => $name,
				'svg'   => $svg,
				'url'   => get_template_directory_uri() . '/assets/icons/' . rawurlencode( $name ) . '.svg',
			);
		}
		ksort( $icons );
		static::$all_icons_cache = $icons;
		return $icons;
	}

	/**
	 * Normalize a class argument into a list of sanitized class names
	 *
	 * @param string|array $value Space-separated string or array of class names
	 * @return array
	 */
	private static function to_class_list( $value ) {
		if ( is_string( $value ) ) {
			$value = preg_split( '/\s+/', $value );
		}
		if ( ! is_array( $value ) ) {
			return array();
		}
		$value = array_map( 'sanitize_html_class', array_filter( $value, 'is_string' ) );
		return array_values( array_filter( $value ) );
	}
}
Blammo_SVG::get_instance();
