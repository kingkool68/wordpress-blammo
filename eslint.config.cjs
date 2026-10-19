const defaultConfig = require('@wordpress/scripts/config/eslint.config.cjs');

module.exports = [
	{
		ignores: ['.claude/worktrees/**', 'build/**', 'vendor/**'],
	},
	...defaultConfig,
];
