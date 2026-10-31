<?php
/**
 * Render blammo/hello-world
 *
 * @var array    $attributes Block attributes (defaults applied, invalid values dropped).
 * @var string   $content    Rendered inner blocks (unused).
 * @var WP_Block $block      Block instance.
 */

$name = trim( (string) ( $attributes['name'] ?? '' ) );
if ( $name === '' ) {
	$name = __( 'world', 'blammo' );
}

/* translators: %s: who to greet, e.g. "world". */
$greeting = sprintf( __( 'Hello, %s!', 'blammo' ), $name );
?>
<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'hello-world-block' ) ); ?>>
	<p class="hello-world-block__greeting"><?php echo esc_html( $greeting ); ?></p>
</div>
