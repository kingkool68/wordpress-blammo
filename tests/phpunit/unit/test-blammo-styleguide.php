<?php

use PHPUnit\Framework\TestCase;

/**
 * The styleguide_path guard: a public query var that reaches locate_template().
 */
class Test_Blammo_Styleguide extends TestCase {

	public function test_request_filter_is_registered() {
		$this->assertContains(
			array( 'request', array( Blammo_Styleguide::get_instance(), 'filter_request' ), 10 ),
			$GLOBALS['blammo_test_hooks']
		);
	}

	/**
	 * @dataProvider data_unsafe_paths
	 */
	public function test_unsafe_paths_are_removed( $raw ) {
		$query_vars = Blammo_Styleguide::get_instance()->filter_request(
			array(
				'styleguide'      => '1',
				'styleguide_path' => $raw,
			)
		);
		$this->assertArrayNotHasKey( 'styleguide_path', $query_vars );
		$this->assertSame( '1', $query_vars['styleguide'] );
	}

	public function data_unsafe_paths() {
		return array(
			'parent directory'       => array( '../' ),
			'nested traversal'       => array( '../../../wp-config' ),
			'absolute path'          => array( '/etc/passwd' ),
			'only dots'              => array( '..' ),
			'only symbols'           => array( '%<>".' ),
			'empty string'           => array( '' ),
			'array value'            => array( array( 'colors' ) ),
			'null'                   => array( null ),
			'leading slash survives' => array( '.../etc/passwd' ),
		);
	}

	/**
	 * @dataProvider data_sanitized_paths
	 */
	public function test_paths_are_sanitized( $raw, $expected ) {
		$query_vars = Blammo_Styleguide::get_instance()->filter_request( array( 'styleguide_path' => $raw ) );
		$this->assertSame( $expected, $query_vars['styleguide_path'] );
	}

	public function data_sanitized_paths() {
		return array(
			'plain page'                   => array( 'colors', 'colors' ),
			'nested page'                  => array( 'blocks/text-image', 'blocks/text-image' ),
			'uppercase is lowered'         => array( 'Colors', 'colors' ),
			'encoded dots lose % and dots' => array( '%2e%2e/', '2e2e/' ),
			'dots inside are dropped'      => array( 'foo/../bar', 'foo//bar' ),
			'php extension loses its dot'  => array( 'colors.php', 'colorsphp' ),
			'null byte is dropped'         => array( "colors\0", 'colors' ),
		);
	}

	public function test_sanitized_paths_never_contain_dots_or_lead_with_a_slash() {
		$inputs = array( '../', '%2e%2e/', '/etc/passwd', '..\\..\\windows', "\x2e\x2e/", 'a/./b', '....//....//etc' );
		foreach ( $inputs as $input ) {
			$path = Blammo_Styleguide::sanitize_path( $input );
			$this->assertStringNotContainsString( '.', $path, $input );
			$this->assertStringNotContainsString( '\\', $path, $input );
			$this->assertFalse( str_starts_with( $path, '/' ), $input );
		}
	}

	public function test_query_vars_without_a_path_are_untouched() {
		$query_vars = array( 'p' => '12' );
		$this->assertSame( $query_vars, Blammo_Styleguide::get_instance()->filter_request( $query_vars ) );
	}

	protected function tearDown(): void {
		$GLOBALS['blammo_test_query_vars'] = array();
		$this->set_page_title( '' );
	}

	private function set_page_title( $title ) {
		$property = new ReflectionProperty( 'Blammo_Styleguide', 'page_title' );
		$property->setValue( null, $title );
	}

	public function test_title_parts_are_untouched_outside_the_styleguide() {
		$parts = array(
			'title' => 'Hello',
			'site'  => 'Test Site',
		);
		$this->assertSame( $parts, Blammo_Styleguide::get_instance()->filter_document_title_parts( $parts ) );
	}

	public function test_index_title_is_the_styleguide_name() {
		$GLOBALS['blammo_test_query_vars'] = array( 'styleguide' => '1' );
		$this->set_page_title( 'Styleguide' );
		$parts = Blammo_Styleguide::get_instance()->filter_document_title_parts( array( 'title' => 'Blog' ) );
		$this->assertSame( array( 'title' => 'Test Site Styleguide' ), $parts );
	}

	public function test_page_title_is_used_when_set() {
		$GLOBALS['blammo_test_query_vars'] = array(
			'styleguide'      => '1',
			'styleguide_path' => 'colors',
		);
		$this->set_page_title( 'Colour palette' );
		$parts = Blammo_Styleguide::get_instance()->filter_document_title_parts( array( 'title' => 'Blog' ) );
		$this->assertSame(
			array(
				'title' => 'Colour palette',
				'site'  => 'Test Site Styleguide',
			),
			$parts
		);
	}

	public function test_title_falls_back_to_the_prettified_path() {
		$GLOBALS['blammo_test_query_vars'] = array(
			'styleguide'      => '1',
			'styleguide_path' => 'blocks/text-image',
		);
		$parts                             = Blammo_Styleguide::get_instance()->filter_document_title_parts( array() );
		$this->assertSame( 'Blocks Text Image', $parts['title'] );
	}

	public function test_home_and_blog_body_classes_are_dropped_on_styleguide_pages() {
		$classes = array( 'home', 'blog', 'wp-theme-blammo' );
		$this->assertSame( $classes, Blammo_Styleguide::get_instance()->filter_body_class( $classes ) );
		$GLOBALS['blammo_test_query_vars'] = array( 'styleguide' => '1' );
		$this->assertSame( array( 'wp-theme-blammo' ), Blammo_Styleguide::get_instance()->filter_body_class( $classes ) );
	}
}
