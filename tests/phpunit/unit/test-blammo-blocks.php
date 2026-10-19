<?php

use PHPUnit\Framework\TestCase;

class Test_Blammo_Blocks extends TestCase {

	public function test_hooks_are_registered() {
		$instance = Blammo_Blocks::get_instance();
		$this->assertContains( array( 'init', array( $instance, 'action_init' ), 10 ), $GLOBALS['blammo_test_hooks'] );
		$this->assertContains( array( 'block_categories_all', array( $instance, 'filter_block_categories_all' ), 10 ), $GLOBALS['blammo_test_hooks'] );
	}

	public function test_category_is_added_once() {
		$categories = Blammo_Blocks::get_instance()->filter_block_categories_all( array( array( 'slug' => 'text' ) ) );
		$this->assertSame( array( 'text', 'blammo' ), array_column( $categories, 'slug' ) );
	}

	public function test_category_is_not_duplicated() {
		$instance   = Blammo_Blocks::get_instance();
		$categories = $instance->filter_block_categories_all( $instance->filter_block_categories_all( array() ) );
		$this->assertSame( array( 'blammo' ), array_column( $categories, 'slug' ) );
	}

	public function test_paths_point_into_build() {
		$this->assertSame( BLAMMO_TESTS_THEME_DIR . '/build/blocks', Blammo_Blocks::get_blocks_path() );
		$this->assertSame( BLAMMO_TESTS_THEME_DIR . '/build/blocks-manifest.php', Blammo_Blocks::get_manifest_path() );
	}
}
