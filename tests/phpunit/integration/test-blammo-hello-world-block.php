<?php
/**
 * Integration tests for blammo/hello-world
 *
 * Runs on wp-env once chore/testing-ci adds the integration suite. Needs build/.
 */
class Test_Blammo_Hello_World_Block extends WP_UnitTestCase {

	/**
	 * Render the block with the given attributes
	 *
	 * @param array $attributes Block attributes
	 * @return string
	 */
	private function render( $attributes = array() ) {
		return render_block(
			array(
				'blockName'    => 'blammo/hello-world',
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	public function test_block_is_registered() {
		$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( 'blammo/hello-world' ) );
	}

	public function test_renders_default_greeting() {
		$html = $this->render();
		$this->assertStringContainsString( 'wp-block-blammo-hello-world', $html );
		$this->assertStringContainsString( 'hello-world-block', $html );
		$this->assertStringContainsString( 'Hello, world!', $html );
	}

	public function test_renders_custom_name() {
		$this->assertStringContainsString( 'Hello, Blammo!', $this->render( array( 'name' => 'Blammo' ) ) );
	}

	public function test_empty_and_whitespace_name_fall_back_to_world() {
		$this->assertStringContainsString( 'Hello, world!', $this->render( array( 'name' => '' ) ) );
		$this->assertStringContainsString( 'Hello, world!', $this->render( array( 'name' => "  \n " ) ) );
	}

	public function test_invalid_attribute_type_falls_back_to_default() {
		$this->assertStringContainsString( 'Hello, world!', $this->render( array( 'name' => array( 'x' ) ) ) );
	}

	public function test_escapes_hostile_input() {
		$html = $this->render( array( 'name' => '<script>alert(1)</script>"><img src=x onerror=alert(1)>' ) );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	public function test_wrapper_supports_reach_the_markup() {
		$html = $this->render(
			array(
				'className'       => 'extra-class',
				'backgroundColor' => 'accent',
			)
		);
		$this->assertStringContainsString( 'extra-class', $html );
		$this->assertStringContainsString( 'has-accent-background-color', $html );
	}

	public function test_two_instances_render_without_fatal() {
		$html = do_blocks( '<!-- wp:blammo/hello-world /--><!-- wp:blammo/hello-world {"name":"again"} /-->' );
		$this->assertStringContainsString( 'Hello, world!', $html );
		$this->assertStringContainsString( 'Hello, again!', $html );
	}
}
