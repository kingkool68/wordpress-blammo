<?php

use PHPUnit\Framework\TestCase;

class Test_Blammo_Abilities extends TestCase {

	protected function setUp(): void {
		$GLOBALS['blammo_test_environment_type'] = 'local';
		$GLOBALS['blammo_test_user_caps']        = array( 'manage_options', 'publish_posts' );
		$GLOBALS['blammo_test_ability_cats']     = array();
		$GLOBALS['blammo_test_abilities']        = array();
		$GLOBALS['blammo_test_inserted_posts']   = array();
		$GLOBALS['blammo_test_insert_result']    = 123;
		$GLOBALS['blammo_test_kses_calls']       = array();
	}

	private function last_inserted_post() {
		$this->assertCount( 1, $GLOBALS['blammo_test_inserted_posts'] );
		// wp_insert_post() unslashes, so compare what it will actually store.
		return array_map( 'stripslashes', $GLOBALS['blammo_test_inserted_posts'][0] );
	}

	public function test_hooks_are_registered() {
		$instance = Blammo_Abilities::get_instance();
		$this->assertContains( array( 'wp_abilities_api_categories_init', array( $instance, 'action_wp_abilities_api_categories_init' ), 10 ), $GLOBALS['blammo_test_hooks'] );
		$this->assertContains( array( 'wp_abilities_api_init', array( $instance, 'action_wp_abilities_api_init' ), 10 ), $GLOBALS['blammo_test_hooks'] );
	}

	public function test_category_is_registered_once() {
		$instance = Blammo_Abilities::get_instance();
		$instance->action_wp_abilities_api_categories_init();
		$GLOBALS['blammo_test_ability_cats']['blammo']['label'] = 'already registered';
		$instance->action_wp_abilities_api_categories_init();
		$this->assertSame( array( 'blammo' ), array_keys( $GLOBALS['blammo_test_ability_cats'] ) );
		$this->assertSame( 'already registered', $GLOBALS['blammo_test_ability_cats']['blammo']['label'] );
	}

	public function test_create_post_is_registered_for_mcp_only() {
		Blammo_Abilities::get_instance()->action_wp_abilities_api_init();
		$args = $GLOBALS['blammo_test_abilities']['blammo/create-post'];
		$this->assertSame( 'blammo', $args['category'] );
		$this->assertTrue( $args['meta']['mcp']['public'] );
		$this->assertFalse( $args['meta']['show_in_rest'] );
		$this->assertArrayNotHasKey( 'public', $args['meta'] );
		$this->assertSame(
			array(
				'readonly'    => false,
				'destructive' => false,
				'idempotent'  => false,
			),
			$args['meta']['annotations']
		);
		$this->assertSame( array( 'Blammo_Abilities', 'can_manage' ), $args['permission_callback'] );
		$this->assertSame( array( 'Blammo_Abilities', 'create_post' ), $args['execute_callback'] );
	}

	public function test_input_schema_is_strict() {
		$schema = Blammo_Abilities::get_create_post_input_schema();
		$this->assertFalse( $schema['additionalProperties'] );
		$this->assertSame( array( 'title' ), $schema['required'] );
		$this->assertSame( array( 'title', 'content', 'excerpt', 'status' ), array_keys( $schema['properties'] ) );
		$this->assertSame( array( 'draft', 'pending', 'private', 'publish' ), $schema['properties']['status']['enum'] );
		$this->assertSame( 'draft', $schema['properties']['status']['default'] );
		foreach ( array( 'title', 'content', 'excerpt' ) as $field ) {
			$this->assertSame( 'string', $schema['properties'][ $field ]['type'] );
			$this->assertArrayHasKey( 'maxLength', $schema['properties'][ $field ] );
		}
		$this->assertSame( 1, $schema['properties']['title']['minLength'] );
	}

	public function test_output_schema_allows_null_edit_link() {
		$schema = Blammo_Abilities::get_create_post_output_schema();
		$this->assertSame( array( 'string', 'null' ), $schema['properties']['edit_link']['type'] );
	}

	public function test_can_manage_requires_local_and_manage_options() {
		$this->assertTrue( Blammo_Abilities::can_manage() );

		$GLOBALS['blammo_test_user_caps'] = array( 'publish_posts' );
		$this->assertFalse( Blammo_Abilities::can_manage() );

		$GLOBALS['blammo_test_user_caps'] = array( 'manage_options' );
		foreach ( array( 'development', 'staging', 'production' ) as $environment ) {
			$GLOBALS['blammo_test_environment_type'] = $environment;
			$this->assertFalse( Blammo_Abilities::can_manage(), $environment );
		}
	}

	public function test_create_post_defaults_to_draft_post_by_current_user() {
		$result = Blammo_Abilities::create_post( array( 'title' => 'Hello' ) );
		$post   = $this->last_inserted_post();
		$this->assertSame( 'post', $post['post_type'] );
		$this->assertSame( '7', (string) $post['post_author'] );
		$this->assertSame( 'Hello', $post['post_title'] );
		$this->assertSame( 'draft', $post['post_status'] );
		$this->assertSame( '', $post['post_content'] );
		$this->assertSame(
			array(
				'id'        => 123,
				'status'    => 'draft',
				'link'      => 'https://example.test/?p=123',
				'edit_link' => 'https://example.test/wp-admin/post.php?post=123&action=edit',
			),
			$result
		);
	}

	public function test_create_post_can_publish() {
		$result = Blammo_Abilities::create_post(
			array(
				'title'  => 'Live',
				'status' => 'publish',
			)
		);
		$this->assertSame( 'publish', $this->last_inserted_post()['post_status'] );
		$this->assertSame( 'publish', $result['status'] );
	}

	public function test_publish_and_private_require_publish_posts() {
		$GLOBALS['blammo_test_user_caps'] = array( 'manage_options' );
		foreach ( array( 'publish', 'private' ) as $status ) {
			$result = Blammo_Abilities::create_post(
				array(
					'title'  => 'Live',
					'status' => $status,
				)
			);
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertSame( 'blammo_cannot_publish', $result->get_error_code() );
		}
		$this->assertSame( array(), $GLOBALS['blammo_test_inserted_posts'] );

		Blammo_Abilities::create_post(
			array(
				'title'  => 'Review me',
				'status' => 'pending',
			)
		);
		$this->assertSame( 'pending', $this->last_inserted_post()['post_status'] );
	}

	public function test_create_post_rejects_unknown_status() {
		$result = Blammo_Abilities::create_post(
			array(
				'title'  => 'Hello',
				'status' => 'trash',
			)
		);
		$this->assertSame( 'blammo_invalid_status', $result->get_error_code() );
		$this->assertSame( array(), $GLOBALS['blammo_test_inserted_posts'] );
	}

	public function test_create_post_rejects_title_that_sanitizes_to_empty() {
		$result = Blammo_Abilities::create_post( array( 'title' => '<b></b>  ' ) );
		$this->assertSame( 'blammo_invalid_title', $result->get_error_code() );
		$this->assertSame( array(), $GLOBALS['blammo_test_inserted_posts'] );
	}

	public function test_hostile_input_is_sanitized() {
		Blammo_Abilities::create_post(
			array(
				'title'   => '<script>alert(1)</script>Hi',
				'content' => '<p>ok</p><script>alert(1)</script>',
				'excerpt' => 'short<script>alert(1)</script>',
			)
		);
		$post = $this->last_inserted_post();
		$this->assertStringNotContainsString( '<script', $post['post_title'] );
		$this->assertSame( '<p>ok</p>', $post['post_content'] );
		$this->assertSame( 'short', $post['post_excerpt'] );
		$this->assertSame(
			array( '<p>ok</p><script>alert(1)</script>', 'short<script>alert(1)</script>' ),
			$GLOBALS['blammo_test_kses_calls']
		);
	}

	public function test_backslashes_in_block_markup_survive_unslashing() {
		$content = '<!-- wp:paragraph {"placeholder":"a \\u003c b \\"q\\""} --><p>C:\\path</p><!-- /wp:paragraph -->';
		Blammo_Abilities::create_post(
			array(
				'title'   => 'Slashes',
				'content' => $content,
			)
		);
		$this->assertSame( $content, $this->last_inserted_post()['post_content'] );
	}

	public function test_insert_error_is_returned() {
		$GLOBALS['blammo_test_insert_result'] = new WP_Error( 'db_insert_error', 'nope' );
		$result                               = Blammo_Abilities::create_post( array( 'title' => 'Hello' ) );
		$this->assertSame( 'db_insert_error', $result->get_error_code() );
	}
}
