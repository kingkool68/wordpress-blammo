<?php
/**
 * Styleguide: every icon in assets/icons/, rendered with Blammo_SVG::get_icon()
 */

$blammo_icons = Blammo_SVG::get_all_icons();

ob_start();
?>
<?php if ( empty( $blammo_icons ) ) : ?>
	<p><?php esc_html_e( 'There are no icons in assets/icons/.', 'blammo' ); ?></p>
<?php else : ?>
	<ul class="blammo-styleguide-icons" style="list-style: none; padding: 0; font-size: 2rem;">
		<?php foreach ( $blammo_icons as $blammo_icon ) : ?>
			<li>
				<?php echo $blammo_icon->svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Blammo_SVG output: theme file with escaped attributes. ?>
				<code style="font-size: 1rem;"><?php echo esc_html( $blammo_icon->label ); ?></code>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
<?php
Blammo_Styleguide::page( __( 'Icons', 'blammo' ), ob_get_clean() );
