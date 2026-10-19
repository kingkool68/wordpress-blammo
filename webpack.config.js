/**
 * Extends the wp-scripts webpack config with the theme-wide entries.
 *
 * Blocks in src/blocks/ are auto-discovered from their block.json files by
 * defaultConfig.entry(). Run with --webpack-copy-php (copies render.php into
 * build/blocks/<name>/) and --blocks-manifest (writes build/blocks-manifest.php).
 */
const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
	...defaultConfig,
	entry: () => ({
		...defaultConfig.entry(),
		blammo: path.resolve(__dirname, 'src/js/blammo.js'),
		editor: path.resolve(__dirname, 'src/js/editor.js'),
	}),
};
