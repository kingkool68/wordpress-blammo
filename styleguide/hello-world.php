<?php
/**
 * Styleguide: blammo/hello-world
 */

ob_start();
?>
<h2><?php esc_html_e( 'Default', 'blammo' ); ?></h2>
<?php echo Blammo_Styleguide::block( 'blammo/hello-world' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered block, escaped in render.php. ?>

<h2><?php esc_html_e( 'Custom name', 'blammo' ); ?></h2>
<?php echo Blammo_Styleguide::block( 'blammo/hello-world', array( 'name' => 'Blammo' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered block, escaped in render.php. ?>

<h2><?php esc_html_e( 'Empty name (falls back to "world")', 'blammo' ); ?></h2>
<?php echo Blammo_Styleguide::block( 'blammo/hello-world', array( 'name' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered block, escaped in render.php. ?>

<h2><?php esc_html_e( 'Hostile input (escaped)', 'blammo' ); ?></h2>
<?php echo Blammo_Styleguide::block( 'blammo/hello-world', array( 'name' => '<script>alert(1)</script>' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered block, escaped in render.php. ?>
<?php
Blammo_Styleguide::page( __( 'Hello World block', 'blammo' ), ob_get_clean() );
