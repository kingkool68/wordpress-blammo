<?php
/**
 * Loader. Every class file bootstraps itself.
 *
 * Never require anything under src/blocks/ or build/blocks/: core loads each
 * block's render.php during a block render.
 */

$blammo_files = array(
	'class-blammo-blocks.php',
	'class-blammo-scripts-and-styles.php',
	'class-blammo-styleguide.php',
	'class-blammo-svg.php',
);
if ( wp_get_environment_type() !== 'production' ) {
	$blammo_files[] = 'class-blammo-staging.php';
}
foreach ( $blammo_files as $blammo_file ) {
	require_once get_template_directory() . '/functions/' . $blammo_file;
}
unset( $blammo_files, $blammo_file );
