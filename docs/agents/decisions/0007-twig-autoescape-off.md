# 0007 — Keep Twig autoescape off (Sprig default); escape explicitly

- Status: superseded by 0008 (2026-10-18)

## Context
Sprig configures Twig with `autoescape => false`, and the starter's templates rely on it. They pass pre-built HTML strings (`the_content`, captured hook output, SVG markup, `get_block_wrapper_attributes()`) straight into templates. Autoescape off is the main XSS risk in this theme.

## Decision
- Keep autoescape off. Every dynamic value is escaped explicitly at output with the filter for its context: `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`, and so on.
- Values that already hold safe HTML are built in PHP, and their names make that clear (e.g. `the_content_html`, `wrapper_attributes`).
- The security reviewer traces every `{{ }}` in changed templates.

## Consequences
- Forgetting a filter is an XSS bug. Tests must feed hostile input to every rendered value (testing.md).
- Templates stay compatible with the starter's patterns and the existing Sprig ecosystem.

## Alternatives considered
- **Turn on autoescape (`html` strategy via the `sprig/twig` filter) and mark pre-built HTML with `|raw`:** safer by default. It was rejected for now because it diverges from the starter and Sprig defaults, and double-escapes `esc_*`-filtered values. It's worth revisiting with a new ADR if escaping bugs keep appearing in review.
