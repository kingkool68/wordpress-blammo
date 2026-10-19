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
		add_editor_style( array( 'build/blammo.css', 'build/editor.css' ) );
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
			// The entry may only import SCSS; don't make every page request an empty script.
			$script = get_template_directory() . '/build/blammo.js';
			if ( file_exists( $script ) && filesize( $script ) > 0 ) {
				wp_register_script(
					'blammo',
					get_template_directory_uri() . '/build/blammo.js',
					$blammo['dependencies'],
					$blammo['version'],
					array(
						'in_footer' => true,
						'strategy'  => 'defer',
					)
				);
			}
		}

		$editor = static::get_asset( 'editor' );
		if ( $editor ) {
			wp_register_script(
				'blammo-editor',
				get_template_directory_uri() . '/build/editor.js',
				$editor['dependencies'],
				$editor['version'],
				array( 'in_footer' => true )
			);
		}
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
