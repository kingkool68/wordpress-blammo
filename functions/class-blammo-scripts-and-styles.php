<?php
/**
 * Registers and enqueues the theme's compiled scripts and stylesheets
 *
 * Handles come from build/<entry>.asset.php (dependencies and version), which
 * wp-scripts writes for every entry. See "What reaches what" in docs/agents/architecture.md.
 */
class Blammo_Scripts_And_Styles {

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new static();
			$instance->setup_actions();
		}
		return $instance;
	}

	/**
	 * Hook into WordPress actions
	 */
	public function setup_actions() {
		add_action( 'after_setup_theme', array( $this, 'action_after_setup_theme' ) );
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'action_enqueue_block_editor_assets' ) );
	}

	/**
	 * Load the theme CSS into the editor canvas (core scopes it to .editor-styles-wrapper)
	 */
	public function action_after_setup_theme() {
		// Core's add_editor_style() only appends an -rtl variant of the first file, on top of the LTR one.
		// Pass the RTL builds instead; the extra "-rtl-rtl.css" core appends doesn't exist and is skipped.
		$suffix = is_rtl() ? '-rtl' : '';
		add_editor_style( array( 'build/blammo' . $suffix . '.css', 'build/editor' . $suffix . '.css' ) );
	}

	/**
	 * Register the front-end and editor handles from the build output
	 */
	public function action_init() {
		$blammo = static::get_asset( 'blammo' );
		if ( $blammo ) {
			if ( file_exists( get_template_directory() . '/build/blammo.css' ) ) {
				wp_register_style(
					'blammo',
					get_template_directory_uri() . '/build/blammo.css',
					array(),
					$blammo['version']
				);
				wp_style_add_data( 'blammo', 'rtl', 'replace' );
			}
		}
		// Entries may only import SCSS; don't make pages request an empty script.
		static::register_script_if_built(
			'blammo',
			'blammo',
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		static::register_script_if_built( 'blammo-editor', 'editor', array( 'in_footer' => true ) );
	}

	/**
	 * Enqueue the front-end handles
	 */
	public function action_wp_enqueue_scripts() {
		wp_enqueue_style( 'blammo' );
		wp_enqueue_script( 'blammo' );
	}

	/**
	 * Enqueue editor JS in the outer editor frame. CSS doesn't belong here: it would never reach the canvas iframe.
	 */
	public function action_enqueue_block_editor_assets() {
		wp_enqueue_script( 'blammo-editor' );
	}

	/**
	 * Register build/<entry>.js under a handle, if it was built and isn't empty
	 *
	 * @param string $handle Script handle
	 * @param string $entry  Build entry name
	 * @param array  $args   wp_register_script() $args (in_footer, strategy)
	 * @return bool Whether the script was registered
	 */
	private static function register_script_if_built( $handle, $entry, $args = array() ) {
		$asset  = static::get_asset( $entry );
		$script = get_template_directory() . '/build/' . $entry . '.js';
		if ( ! $asset || ! file_exists( $script ) || filesize( $script ) === 0 ) {
			return false;
		}
		return wp_register_script(
			$handle,
			get_template_directory_uri() . '/build/' . $entry . '.js',
			$asset['dependencies'],
			$asset['version'],
			$args
		);
	}

	/**
	 * Read the dependencies and version wp-scripts wrote for a build entry
	 *
	 * @param string $entry Entry name, e.g. 'blammo' for build/blammo.asset.php
	 * @return array|null Array with 'dependencies' and 'version' keys, or null if the entry isn't built
	 */
	public static function get_asset( $entry = '' ) {
		if ( ! is_string( $entry ) || ! preg_match( '/^[a-z0-9-]+$/', $entry ) ) {
			return null;
		}
		$file = get_template_directory() . '/build/' . $entry . '.asset.php';
		if ( ! file_exists( $file ) ) {
			return null;
		}
		$asset = require $file;
		if ( ! is_array( $asset ) ) {
			return null;
		}
		return array(
			'dependencies' => isset( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) ? $asset['dependencies'] : array(),
			'version'      => isset( $asset['version'] ) ? (string) $asset['version'] : null,
		);
	}
}
Blammo_Scripts_And_Styles::get_instance();
