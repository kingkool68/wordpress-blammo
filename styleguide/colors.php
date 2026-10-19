<?php
/**
 * Styleguide: the theme.json colour palette
 */

$blammo_palette = wp_get_global_settings( array( 'color', 'palette', 'theme' ) );

ob_start();
?>
<?php if ( empty( $blammo_palette ) ) : ?>
	<p><?php esc_html_e( 'The theme defines no colours.', 'blammo' ); ?></p>
<?php else : ?>
	<ul class="blammo-styleguide-swatches" style="list-style: none; padding: 0;">
		<?php foreach ( $blammo_palette as $blammo_color ) : ?>
			<?php
			$blammo_slug = isset( $blammo_color['slug'] ) ? sanitize_html_class( $blammo_color['slug'] ) : '';
			if ( $blammo_slug === '' ) {
				continue;
			}
			?>
			<li class="blammo-styleguide-swatch" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; margin-bottom: 1rem;">
				<span class="blammo-styleguide-swatch__chip" style="display: block; width: 4rem; height: 4rem; border: 1px solid var(--wp--preset--color--border); background-color: var(--wp--preset--color--<?php echo esc_attr( $blammo_slug ); ?>);"></span>
				<strong><?php echo esc_html( $blammo_color['name'] ?? $blammo_slug ); ?></strong>
				<code>--wp--preset--color--<?php echo esc_html( $blammo_slug ); ?></code>
				<code><?php echo esc_html( $blammo_color['color'] ?? '' ); ?></code>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
<?php
Blammo_Styleguide::page( __( 'Colors', 'blammo' ), ob_get_clean() );
