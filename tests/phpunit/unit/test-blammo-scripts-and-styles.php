<?php

use PHPUnit\Framework\TestCase;

class Test_Blammo_Scripts_And_Styles extends TestCase {

	public function test_hooks_are_registered() {
		$instance = Blammo_Scripts_And_Styles::get_instance();
		$hooks    = $GLOBALS['blammo_test_hooks'];
		$this->assertContains( array( 'after_setup_theme', array( $instance, 'action_after_setup_theme' ), 10 ), $hooks );
		$this->assertContains( array( 'init', array( $instance, 'action_init' ), 10 ), $hooks );
		$this->assertContains( array( 'wp_enqueue_scripts', array( $instance, 'action_wp_enqueue_scripts' ), 10 ), $hooks );
		$this->assertContains( array( 'enqueue_block_editor_assets', array( $instance, 'action_enqueue_block_editor_assets' ), 10 ), $hooks );
	}

	/**
	 * @dataProvider data_invalid_entries
	 */
	public function test_invalid_entry_names_are_rejected( $entry ) {
		$this->assertNull( Blammo_Scripts_And_Styles::get_asset( $entry ) );
	}

	public function data_invalid_entries() {
		return array(
			'traversal' => array( '../functions' ),
			'slash'     => array( 'blocks/foo' ),
			'missing'   => array( 'does-not-exist' ),
			'empty'     => array( '' ),
			'array'     => array( array( 'blammo' ) ),
		);
	}

	public function test_built_entry_returns_dependencies_and_version() {
		if ( ! file_exists( get_template_directory() . '/build/blammo.asset.php' ) ) {
			$this->markTestSkipped( 'Run npm run build first.' );
		}
		$asset = Blammo_Scripts_And_Styles::get_asset( 'blammo' );
		$this->assertIsArray( $asset['dependencies'] );
		$this->assertIsString( $asset['version'] );
		$this->assertNotSame( '', $asset['version'] );
	}
}
