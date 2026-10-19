<?php

use PHPUnit\Framework\TestCase;

class Test_Blammo_SVG extends TestCase {

	public function test_icon_is_decorative_by_default() {
		$svg = Blammo_SVG::get_icon( 'close' );
		$this->assertStringStartsWith( '<svg class="icon icon-close" aria-hidden="true" focusable="false"', $svg );
		$this->assertStringNotContainsString( 'role="img"', $svg );
	}

	public function test_label_makes_the_icon_an_image_and_is_escaped() {
		$svg = Blammo_SVG::get_icon( 'close', array( 'label' => 'Close "menu" <script>' ) );
		$this->assertStringContainsString( 'role="img" aria-label="Close &quot;menu&quot; &lt;script&gt;"', $svg );
		$this->assertStringNotContainsString( 'aria-hidden', $svg );
		$this->assertStringNotContainsString( '<script>', $svg );
	}

	public function test_classes_are_sanitized_and_deduplicated() {
		$svg = Blammo_SVG::get_icon(
			'menu',
			array( 'add_css_class' => array( 'icon', 'x"onmouseover="alert(1)', 'extra' ) )
		);
		$this->assertStringContainsString( 'class="icon icon-menu xonmouseoveralert1 extra"', $svg );
		$this->assertStringNotContainsString( 'onmouseover=', $svg );
	}

	public function test_attributes_are_added_to_the_root_svg_only() {
		$svg = Blammo_SVG::get_icon( 'close' );
		$this->assertSame( 1, substr_count( $svg, 'aria-hidden' ) );
	}

	/**
	 * @dataProvider data_invalid_icon_names
	 */
	public function test_invalid_icon_names_return_an_empty_string( $name ) {
		$this->assertSame( '', Blammo_SVG::get_icon( $name ) );
	}

	public function data_invalid_icon_names() {
		return array(
			'traversal'      => array( '../../../../wp-config' ),
			'absolute'       => array( '/etc/passwd' ),
			'missing'        => array( 'does-not-exist' ),
			'uppercase'      => array( 'Close' ),
			'with extension' => array( 'close.svg' ),
			'empty'          => array( '' ),
			'array'          => array( array( 'close' ) ),
		);
	}

	public function test_get_all_icons_lists_every_svg() {
		$icons = Blammo_SVG::get_all_icons();
		$files = glob( get_template_directory() . '/assets/icons/*.svg' );
		$this->assertCount( count( $files ), $icons );
		$this->assertSame( array_keys( $icons ), array_values( array_unique( array_keys( $icons ) ) ) );
		$this->assertArrayHasKey( 'close', $icons );
		$this->assertSame( 'https://example.test/wp-content/themes/blammo/assets/icons/close.svg', $icons['close']->url );
	}
}
