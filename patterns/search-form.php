<?php
/**
 * Title: Search form
 * Slug: blammo/search-form
 * Categories: blammo
 * Inserter: no
 * Description: A search form with a visually hidden label. Used by the search and 404 templates.
 */

// serialize_block() JSON-encodes the attributes, so translated strings can't break the block comment.
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- serialize_block() output; core escapes the attributes on render.
echo serialize_block(
	array(
		'blockName'    => 'core/search',
		'attrs'        => array(
			'label'      => _x( 'Search', 'search form label', 'blammo' ),
			'showLabel'  => false,
			'buttonText' => _x( 'Search', 'search button text', 'blammo' ),
		),
		'innerBlocks'  => array(),
		'innerHTML'    => '',
		'innerContent' => array(),
	)
);
