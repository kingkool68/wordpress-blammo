<?php
/**
 * Styleguide: headings, body text and the theme.json font sizes
 */

$blammo_font_sizes = wp_get_global_settings( array( 'typography', 'fontSizes', 'theme' ) );

ob_start();

// Literal core block markup: no values are interpolated into block comments.
echo do_blocks( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered from literal markup.
	'<!-- wp:heading --><h2 class="wp-block-heading">Heading 2</h2><!-- /wp:heading -->
	<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Heading 3</h3><!-- /wp:heading -->
	<!-- wp:heading {"level":4} --><h4 class="wp-block-heading">Heading 4</h4><!-- /wp:heading -->
	<!-- wp:paragraph --><p>Body text with <a href="#">a link</a>, <strong>bold</strong>, <em>italic</em> and <code>code</code>.</p><!-- /wp:paragraph -->
	<!-- wp:quote --><blockquote class="wp-block-quote"><!-- wp:paragraph --><p>A quotation.</p><!-- /wp:paragraph --><cite>A citation</cite></blockquote><!-- /wp:quote -->
	<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>List item</li><!-- /wp:list-item --><!-- wp:list-item --><li>List item</li><!-- /wp:list-item --></ul><!-- /wp:list -->
	<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Button</a></div><!-- /wp:button --></div><!-- /wp:buttons -->'
);
?>
<h2><?php esc_html_e( 'Font sizes', 'blammo' ); ?></h2>
<?php foreach ( (array) $blammo_font_sizes as $blammo_font_size ) : ?>
	<?php
	$blammo_slug = isset( $blammo_font_size['slug'] ) ? sanitize_html_class( $blammo_font_size['slug'] ) : '';
	if ( $blammo_slug === '' ) {
		continue;
	}
	?>
	<p class="has-<?php echo esc_attr( $blammo_slug ); ?>-font-size">
		<?php echo esc_html( $blammo_font_size['name'] ?? $blammo_slug ); ?>
		<code>--wp--preset--font-size--<?php echo esc_html( $blammo_slug ); ?></code>
	</p>
<?php endforeach; ?>
<?php
Blammo_Styleguide::page( __( 'Typography', 'blammo' ), ob_get_clean() );
