<?php
/**
 * Bootstrap for pure unit tests: no WordPress, no database.
 *
 * Stubs only the WordPress functions the classes under test call. Integration
 * tests (WP_UnitTestCase on wp-env) live in tests/phpunit/integration/.
 */

require_once dirname( __DIR__, 3 ) . '/vendor/autoload.php';

define( 'BLAMMO_TESTS_THEME_DIR', dirname( __DIR__, 3 ) );

/*
 * Hooks are recorded so tests can assert what a class registers.
 */
$GLOBALS['blammo_test_hooks'] = array();

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['blammo_test_hooks'][] = array( $hook, $callback, $priority );
	return true;
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	return add_filter( $hook, $callback, $priority, $accepted_args );
}

function __( $text, $domain = 'default' ) {
	return $text;
}

/*
 * Query vars and site name are set per test through these globals.
 */
$GLOBALS['blammo_test_query_vars'] = array();

function get_query_var( $query_var, $default_value = '' ) {
	return $GLOBALS['blammo_test_query_vars'][ $query_var ] ?? $default_value;
}

function get_bloginfo( $show = '', $filter = 'raw' ) {
	return $show === 'name' ? 'Test Site' : '';
}

function get_template_directory() {
	return BLAMMO_TESTS_THEME_DIR;
}

function get_template_directory_uri() {
	return 'https://example.test/wp-content/themes/blammo';
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function sanitize_html_class( $classname, $fallback = '' ) {
	$sanitized = preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', $classname );
	$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', $sanitized );
	return ( $sanitized === '' && $fallback ) ? sanitize_html_class( $fallback ) : $sanitized;
}

/*
 * Abilities API, capabilities and post creation. Tests set the environment
 * type, capabilities and wp_insert_post() result through these globals; the
 * register and insert calls are recorded.
 */
$GLOBALS['blammo_test_environment_type'] = 'local';
$GLOBALS['blammo_test_user_caps']        = array();
$GLOBALS['blammo_test_ability_cats']     = array();
$GLOBALS['blammo_test_abilities']        = array();
$GLOBALS['blammo_test_inserted_posts']   = array();
$GLOBALS['blammo_test_insert_result']    = 123;
$GLOBALS['blammo_test_kses_calls']       = array();

require_once dirname( __DIR__ ) . '/stubs/class-wp-error.php';

function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

function wp_get_environment_type() {
	return $GLOBALS['blammo_test_environment_type'];
}

function current_user_can( $capability ) {
	return in_array( $capability, $GLOBALS['blammo_test_user_caps'], true );
}

function get_current_user_id() {
	return 7;
}

function get_post_type_object( $post_type ) {
	return (object) array( 'cap' => (object) array( 'publish_posts' => 'publish_posts' ) );
}

function wp_has_ability_category( $slug ) {
	return isset( $GLOBALS['blammo_test_ability_cats'][ $slug ] );
}

function wp_register_ability_category( $slug, $args ) {
	$GLOBALS['blammo_test_ability_cats'][ $slug ] = $args;
	return true;
}

function wp_register_ability( $name, $args ) {
	$GLOBALS['blammo_test_abilities'][ $name ] = $args;
	return true;
}

function sanitize_text_field( $str ) {
	return trim( preg_replace( '/[\r\n\t ]+/', ' ', preg_replace( '/<[^>]*>/', '', (string) $str ) ) );
}

function wp_kses_post( $data ) {
	$GLOBALS['blammo_test_kses_calls'][] = $data;
	return preg_replace( '#<script\b[^>]*>.*?</script>#is', '', $data );
}

function wp_slash( $value ) {
	return is_array( $value ) ? array_map( 'wp_slash', $value ) : ( is_string( $value ) ? addslashes( $value ) : $value );
}

function wp_insert_post( $postarr, $wp_error = false ) {
	$GLOBALS['blammo_test_inserted_posts'][] = $postarr;
	return $GLOBALS['blammo_test_insert_result'];
}

function get_permalink( $post_id ) {
	return 'https://example.test/?p=' . $post_id;
}

function get_edit_post_link( $post_id, $context = 'display' ) {
	return 'https://example.test/wp-admin/post.php?post=' . $post_id . '&action=edit';
}

require_once BLAMMO_TESTS_THEME_DIR . '/functions/class-blammo-abilities.php';
require_once BLAMMO_TESTS_THEME_DIR . '/functions/class-blammo-blocks.php';
require_once BLAMMO_TESTS_THEME_DIR . '/functions/class-blammo-styleguide.php';
require_once BLAMMO_TESTS_THEME_DIR . '/functions/class-blammo-svg.php';
require_once BLAMMO_TESTS_THEME_DIR . '/functions/class-blammo-scripts-and-styles.php';
