<?php
/**
 * Abilities (WordPress Abilities API) exposed to the blammo-wp MCP server.
 *
 * Only loaded when wp_get_environment_type() is 'local'. Every ability also
 * re-checks that, plus manage_options, at execution time (see can_manage()).
 */
class Blammo_Abilities {

	/**
	 * Post statuses blammo/create-post may set
	 *
	 * @var string[]
	 */
	const POST_STATUSES = array( 'draft', 'pending', 'private', 'publish' );

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new static();
			$instance->setup_actions();
		}
		return $instance;
	}

	/**
	 * Hook into WordPress actions
	 */
	public function setup_actions() {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'action_wp_abilities_api_categories_init' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'action_wp_abilities_api_init' ) );
	}

	/**
	 * Register the blammo ability category
	 */
	public function action_wp_abilities_api_categories_init() {
		if ( wp_has_ability_category( 'blammo' ) ) {
			return;
		}
		wp_register_ability_category(
			'blammo',
			array(
				'label'       => __( 'Blammo', 'blammo' ),
				'description' => __( 'Local-only abilities provided by the Blammo theme.', 'blammo' ),
			)
		);
	}

	/**
	 * Register the theme's abilities
	 */
	public function action_wp_abilities_api_init() {
		wp_register_ability(
			'blammo/create-post',
			array(
				'label'               => __( 'Create Post', 'blammo' ),
				'description'         => __( 'Creates a post authored by the current user. Content is block markup and is filtered through wp_kses_post. Status defaults to draft; publish makes the post publicly visible.', 'blammo' ),
				'category'            => 'blammo',
				'input_schema'        => static::get_create_post_input_schema(),
				'output_schema'       => static::get_create_post_output_schema(),
				'execute_callback'    => array( static::class, 'create_post' ),
				'permission_callback' => array( static::class, 'can_manage' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					),
					'mcp'          => array(
						'public' => true,
					),
					'show_in_rest' => false,
				),
			)
		);
	}

	/**
	 * Whether the current request may run Blammo abilities
	 *
	 * @return bool True for users who can manage_options on a 'local' environment
	 */
	public static function can_manage() {
		return wp_get_environment_type() === 'local' && current_user_can( 'manage_options' );
	}

	/**
	 * JSON Schema for blammo/create-post input
	 *
	 * @return array
	 */
	public static function get_create_post_input_schema() {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'title'   => array(
					'type'        => 'string',
					'minLength'   => 1,
					'maxLength'   => 200,
					'description' => __( 'Post title (plain text).', 'blammo' ),
				),
				'content' => array(
					'type'        => 'string',
					'maxLength'   => 100000,
					'default'     => '',
					'description' => __( 'Post content as block markup.', 'blammo' ),
				),
				'excerpt' => array(
					'type'        => 'string',
					'maxLength'   => 1000,
					'default'     => '',
					'description' => __( 'Optional manual excerpt.', 'blammo' ),
				),
				'status'  => array(
					'type'        => 'string',
					'enum'        => static::POST_STATUSES,
					'default'     => 'draft',
					'description' => __( 'Post status. Defaults to draft.', 'blammo' ),
				),
			),
			'required'             => array( 'title' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * JSON Schema for blammo/create-post output
	 *
	 * @return array
	 */
	public static function get_create_post_output_schema() {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'        => array( 'type' => 'integer' ),
				'status'    => array( 'type' => 'string' ),
				'link'      => array( 'type' => 'string' ),
				'edit_link' => array( 'type' => array( 'string', 'null' ) ),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Execute blammo/create-post
	 *
	 * @param array $input Input validated against get_create_post_input_schema()
	 * @return array|WP_Error The created post's id, status and links, or an error
	 */
	public static function create_post( $input = array() ) {
		$defaults = array(
			'title'   => '',
			'content' => '',
			'excerpt' => '',
			'status'  => 'draft',
		);
		$input    = wp_parse_args( $input, $defaults );

		$title = sanitize_text_field( (string) $input['title'] );
		if ( $title === '' ) {
			return new WP_Error( 'blammo_invalid_title', __( 'A title is required.', 'blammo' ) );
		}
		if ( ! in_array( $input['status'], static::POST_STATUSES, true ) ) {
			return new WP_Error( 'blammo_invalid_status', __( 'Invalid post status.', 'blammo' ) );
		}
		$post_type_object = get_post_type_object( 'post' );
		if ( $input['status'] !== 'draft' && $input['status'] !== 'pending' && ! current_user_can( $post_type_object->cap->publish_posts ) ) {
			return new WP_Error( 'blammo_cannot_publish', __( 'You are not allowed to publish posts.', 'blammo' ) );
		}

		// The claude user has unfiltered_html, so core wouldn't filter content; always run kses.
		// wp_insert_post() unslashes its data, so slash it first or block JSON loses backslashes.
		$post_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'post',
					'post_author'  => get_current_user_id(),
					'post_title'   => $title,
					'post_content' => wp_kses_post( (string) $input['content'] ),
					'post_excerpt' => wp_kses_post( (string) $input['excerpt'] ),
					'post_status'  => $input['status'],
				)
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		return array(
			'id'        => (int) $post_id,
			'status'    => $input['status'],
			'link'      => (string) get_permalink( $post_id ),
			'edit_link' => get_edit_post_link( $post_id, 'raw' ),
		);
	}
}
Blammo_Abilities::get_instance();
