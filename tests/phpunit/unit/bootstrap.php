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

require_once BLAMMO_TESTS_THEME_DIR . '/functions/class-blammo-blocks.php';
require_once BLAMMO_TESTS_THEME_DIR . '/functions/class-blammo-styleguide.php';
require_once BLAMMO_TESTS_THEME_DIR . '/functions/class-blammo-svg.php';
require_once BLAMMO_TESTS_THEME_DIR . '/functions/class-blammo-scripts-and-styles.php';
