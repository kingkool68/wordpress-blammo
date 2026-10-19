# Conventions

## Naming
| Thing | Pattern | Example |
|---|---|---|
| PHP class | `Blammo_<Name>` | `Blammo_Scripts_And_Styles` |
| Class file | `functions/class-blammo-<name>.php` | `functions/class-blammo-svg.php` |
| Block class | `src/blocks/<name>/class-blammo-<name>-block.php` | `Blammo_Text_Image_Block` |
| Block template | `src/blocks/<name>/<name>-block.twig` (names are global across all Twig dirs) | `text-image-block.twig` |
| Block name | `blammo/<name>` | `blammo/text-image` |
| Block category | `blammo` | |
| Hooks the theme fires | `blammo/<area>/<thing>` (slashes allowed) | `blammo/header/show_nav` |
| Script and style handles | `blammo-<name>` (front-end main is `blammo`) | `blammo-editor` |
| Constants | `BLAMMO_<NAME>` | `BLAMMO_VERSION` |
| WP-CLI command | `wp blammo <subcommand>` | |
| Ability names | `blammo/<verb>-<noun>` | `blammo/get-theme-status` |
| CSS classes | BEM-ish: `block`, `block__element`, `block--modifier` | `text-image-block--align-left` |

## PHP
- Follow WordPress Coding Standards 3 through `phpcs.xml.dist`, with these exceptions:
  - **no Yoda conditions** (write `$x === 'y'`);
  - slash-delimited hook names are allowed;
  - file doc comments aren't required.
- PHP 8.1+ syntax is fine (the site runs 8.4). Don't use namespaces; keep the `Blammo_` prefix.
- Use the singleton plus `setup_actions()` / `setup_filters()` pattern. See [architecture.md](architecture.md).
- Validate and sanitize input early, and escape output late. Check capabilities and nonces for every state change.
- Prefer WP APIs over raw SQL. If SQL is unavoidable, always use `$wpdb->prepare()`.
- Give public static methods a docblock with `@param` and `@return`.

## Twig
- **Autoescape is OFF.** Every `{{ var }}` needs an explicit escape filter unless the value is already-escaped HTML built in PHP. When that's the case, name it to say so (e.g. `the_content_html`) and say where it is escaped.
  - text: `|esc_html` · attributes: `|esc_attr` · URLs: `|esc_url` · class names: `|sanitize_html_class` · rich HTML: `|wp_kses_post` (theme-registered)
  - Only registered filters exist. See architecture.md for the list. An unknown filter is a fatal error.
- No business logic or queries in Twig. Data arrives in `$context`.
- Templates live next to their component (`src/blocks/<name>/<name>-block.twig`), or in `twig/` for page-level templates. Every template name must be unique across all Twig dirs.
- Never call `Sprig::render()` before `init` priority 10.

## SCSS
- The entry is `src/scss/blammo.scss`. Tokens live in `src/scss/base/` (`_colors`, `_fonts`, `_variables`, `_functions`, `_mixins`, `_typography`).
- Use the module system (`@use` / `@forward`), not `@import`.
- Don't hard-code colours or sizes in components; use tokens and the `rem()` helper.
- Respect `prefers-reduced-motion`. Focus styles must be visible; never use `outline: none` without a replacement.

## JavaScript
- Use ES modules and `@wordpress/dom-ready` for front-end init. Use `@wordpress/*` packages in the editor.
- Lint with `wp-scripts lint-js` and format with `@wordpress/prettier-config`.
- Don't use jQuery.

## Git and PRs
- Branches are `<type>/<slug>`, where type is `feat|fix|chore|docs|refactor|test`.
- Use Conventional Commits, e.g. `feat(blocks): add text-image block`.
- Keep PRs to one concern. If a task grows, split it into stacked PRs.
