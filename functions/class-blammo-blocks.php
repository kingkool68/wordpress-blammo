<?php
/**
 * Registers the theme's blocks and its block and pattern categories
 */
class Blammo_Blocks {

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new static();
			$instance->setup_actions();
			$instance->setup_filters();
		}
		return $instance;
	}

	/**
	 * Hook into WordPress actions
	 */
	public function setup_actions() {
		add_action( 'init', array( $this, 'action_init' ) );
	}

	/**
	 * Hook into WordPress filters
	 */
	public function setup_filters() {
		add_filter( 'block_categories_all', array( $this, 'filter_block_categories_all' ) );
	}

	/**
	 * Register the pattern category and every block in build/blocks-manifest.php
	 */
	public function action_init() {
		register_block_pattern_category(
			'blammo',
			array(
				'label' => __( 'Blammo', 'blammo' ),
			)
		);

		$manifest = static::get_manifest_path();
		if ( ! file_exists( $manifest ) ) {
			// build/ is missing (npm run build hasn't run): no blocks, no fatal.
			return;
		}
		wp_register_block_types_from_metadata_collection( static::get_blocks_path(), $manifest );
	}

	/**
	 * Add the Blammo block category to the inserter
	 *
	 * @param array $categories Block categories
	 * @return array Modified block categories
	 */
	public function filter_block_categories_all( $categories = array() ) {
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && $category['slug'] === 'blammo' ) {
				return $categories;
			}
		}
		$categories[] = array(
			'slug'  => 'blammo',
			'title' => __( 'Blammo', 'blammo' ),
			'icon'  => null,
		);
		return $categories;
	}

	/**
	 * Absolute path to the compiled blocks directory
	 *
	 * @return string
	 */
	public static function get_blocks_path() {
		return get_template_directory() . '/build/blocks';
	}

	/**
	 * Absolute path to the blocks manifest written by `wp-scripts build --blocks-manifest`
	 *
	 * @return string
	 */
	public static function get_manifest_path() {
		return get_template_directory() . '/build/blocks-manifest.php';
	}
}
Blammo_Blocks::get_instance();
