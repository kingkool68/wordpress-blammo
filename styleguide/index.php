<?php
/**
 * Styleguide index: links to every page in styleguide/
 */

$blammo_pages = array();
foreach ( (array) glob( get_template_directory() . '/styleguide/*.php' ) as $blammo_file ) {
	$blammo_slug = basename( $blammo_file, '.php' );
	if ( $blammo_slug === 'index' ) {
		continue;
	}
	$blammo_pages[ $blammo_slug ] = ucwords( str_replace( array( '-', '_' ), ' ', $blammo_slug ) );
}
ksort( $blammo_pages );

ob_start();
?>
<ul>
	<?php foreach ( $blammo_pages as $blammo_slug => $blammo_label ) : ?>
		<li><a href="<?php echo esc_url( home_url( '/styleguide/' . $blammo_slug . '/' ) ); ?>"><?php echo esc_html( $blammo_label ); ?></a></li>
	<?php endforeach; ?>
</ul>
<?php
Blammo_Styleguide::page( __( 'Styleguide', 'blammo' ), ob_get_clean() );
