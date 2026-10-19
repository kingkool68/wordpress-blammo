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
}
