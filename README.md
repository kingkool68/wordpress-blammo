# Blammo

A block WordPress theme built with an agentic workflow. It is based on the conventions of
[wordpress-rh-starter-theme](https://github.com/kingkool68/wordpress-rh-starter-theme).

- Theme type: full block (FSE) theme: `templates/`, `parts/`, `patterns/`, with `theme.json` design tokens
- Blocks: native dynamic blocks (`block.json` + `render.php`)
- Styleguide: the optional [wp-styleguide](https://github.com/kingkool68/wp-styleguide) dev plugin
- Build: `@wordpress/scripts`
- Tests: PHPUnit, Jest and Playwright running on `@wordpress/env`

## Setup
```bash
composer install && npm install
npm run build        # required: blocks and theme CSS/JS load from build/
```
Requires WordPress 7.0+ and PHP 8.1+. The styleguide (`/styleguide/`) needs the optional
[wp-styleguide](https://github.com/kingkool68/wp-styleguide) plugin.

## Checks
```bash
npm run lint && composer lint   # ESLint + Stylelint, PHPCS
composer test:unit              # PHPUnit unit suite (no WordPress needed)
```
Agent and contributor docs: [CLAUDE.md](CLAUDE.md) and [docs/agents/](docs/agents/).
