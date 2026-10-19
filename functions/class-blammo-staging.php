<?php
/**
 * Behaviour for non-production environments. Only loaded when wp_get_environment_type() isn't 'production'.
 */
class Blammo_Staging {

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
		add_filter( 'pre_option_blog_public', array( $this, 'filter_pre_option_blog_public' ) );
	}

	/**
	 * Keep non-production sites out of search engines
	 *
	 * @return string '0'. A string, because some SEO plugins compare it strictly to '0'.
	 */
	public function filter_pre_option_blog_public() {
		return '0';
	}
}
Blammo_Staging::get_instance();
