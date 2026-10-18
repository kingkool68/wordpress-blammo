# 0002 — `@wordpress/scripts` is the only build tool

- Status: accepted (2026-10-18)

## Context
The starter uses Gulp (dart-sass, esbuild). Native blocks need JSX and WordPress externals, which `@wordpress/scripts` handles natively, along with `*.asset.php` dependency manifests.

## Decision
Drop Gulp. Blocks live in **`src/blocks/<name>/`**, the wp-scripts default source dir. That means:
- zero-config auto-discovery of `block.json`;
- the build goes to `build/blocks/<name>/`, with `block.json` copied there.

`webpack.config.js` exports the default config, plus the theme entries `blammo` and `editor`, merged with `...defaultConfig.entry()`. SCSS is compiled through wp-scripts. Lint, format and tests also use wp-scripts commands.

## Consequences
- One toolchain to maintain. Enqueueing reads `build/*.asset.php` for deps and version, which replaces mtime cache busting.
- The starter's `.src.js` suffix convention goes away. Sources live in `src/`, and output goes to `build/`.
- Block PHP classes and Twig live beside the block source in `src/blocks/<name>/`. The PHP loader and `sprig/roots` point there.

## Alternatives considered
- **Keep Gulp, plus wp-scripts for blocks:** rejected because it means two toolchains.
- **Keep blocks in a root `blocks/` dir:** rejected because it needs a multi-config webpack setup to get correct `build/blocks/` output and the block.json copy.
