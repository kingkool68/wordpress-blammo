# Conventions

## Naming
| Thing | Pattern | Example |
|---|---|---|
| PHP class | `Blammo_<Name>` | `Blammo_Scripts_And_Styles` |
| Class file | `functions/class-blammo-<name>.php` | `functions/class-blammo-svg.php` |
| Block render file | `src/blocks/<name>/render.php` | |
| Block name | `blammo/<name>` | `blammo/text-image` |
| Pattern | `patterns/<name>.php`, `Slug: blammo/<name>`, category `blammo` | `blammo/hero` |
| Template / part | `templates/<hierarchy-slug>.html` / `parts/<name>.html` | `single.html`, `header.html` |
| `theme.json` preset slug | kebab-case, role-based not value-based | `primary`, not `blue` |
| Block category | `blammo` | |
| Hooks the theme fires | `blammo/<area>/<thing>` (slashes allowed) | `blammo/svg/icons` |
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

## Templates, parts, patterns and `render.php`
- Templates and parts are block markup only. Build them in the site editor or by hand, then copy the markup into the file; keep it valid (no block-validation errors in the editor).
- Exactly one `<main>` per template (a `core/group` with `"tagName":"main"`). Core adds a skip link at the start of the page that targets it.
- No hard-coded user-facing text in `.html` files: put it in a pattern and reference it with `<!-- wp:pattern {"slug":"blammo/<name>"} /-->`.
- Patterns escape translated strings (`esc_html_e( '…', 'blammo' )`) and theme URLs (`esc_url( get_theme_file_uri( … ) )`). They hold markup, not per-request data; that's a block's job.
- `render.php` rules are in [blocks.md](blocks.md): escape at output, `get_block_wrapper_attributes()`, no declarations.
- Block attribute JSON in markup is written by the editor or `serialize_block()`, never by string-interpolating values.

## SCSS
- **Tokens live in `theme.json`** (ADR 0008). SCSS reads them as custom properties: `var(--wp--preset--color--<slug>)`, `var(--wp--preset--font-size--<slug>)`, `var(--wp--preset--spacing--<slug>)`, `var(--wp--custom--<path>)`. Never redefine a token in SCSS.
- Style core blocks in `theme.json` `styles.blocks` first; use SCSS (`wp_enqueue_block_style()`) only for what it can't express.
- The entry is `src/scss/blammo.scss`. `src/scss/base/` holds only `_functions`, `_mixins` and `_breakpoints` (media queries can't use custom properties).
- Use the module system (`@use` / `@forward`), not `@import`.
- Don't hard-code colours or sizes in components; use presets and the `rem()` helper.
- Respect `prefers-reduced-motion`. Focus styles must be visible; never use `outline: none` without a replacement.

## JavaScript
- Use ES modules and `@wordpress/dom-ready` for front-end init. Use `@wordpress/*` packages in the editor.
- Lint with `wp-scripts lint-js` and format with `@wordpress/prettier-config`.
- Don't use jQuery.

## Git and PRs
- Branches are `<type>/<slug>`, where type is `feat|fix|chore|docs|refactor|test`.
- Use Conventional Commits, e.g. `feat(blocks): add text-image block`.
- Keep PRs to one concern. If a task grows, split it into stacked PRs.
