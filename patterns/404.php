<?php
/**
 * Title: Page not found
 * Slug: blammo/404
 * Categories: blammo
 * Inserter: no
 * Description: The 404 template's heading, message and search form.
 */

?>
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading"><?php echo esc_html_x( 'Page not found', '404 heading', 'blammo' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'The page you were looking for doesn’t exist or has moved. Try searching for it.', 'blammo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:pattern {"slug":"blammo/search-form"} /-->
