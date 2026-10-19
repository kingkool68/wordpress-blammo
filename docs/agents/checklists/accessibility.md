# Accessibility checklist (WCAG 2.2 AA)

## Structure
- [ ] Landmarks: one `<main>`, a `<header>` / `<footer>`, `<nav>` with an accessible name when there are several.
- [ ] Headings form a logical outline, with one `h1` per page and no skipped levels inside components.
- [ ] Each template has exactly one `<main>` (core adds a skip link at the start of the page that targets it). Styleguide pages print their own skip link and `<main id="main">`.
- [ ] Header and footer parts render as `<header>` / `<footer>` (template part `area` or `tagName`).
- [ ] `lang` is set on `<html>` via `language_attributes()` (core does this in block templates; styleguide pages must do it).

## Interaction
- [ ] Everything is operable by keyboard alone, with no keyboard traps.
- [ ] Focus order matches the visual order. Focus is visible (≥ 3:1 contrast) and not hidden by sticky headers (2.4.11).
- [ ] Each `core/navigation` has a distinct accessible name when there are several, and its mobile overlay is keyboard-operable.
- [ ] Custom menus and disclosures:
  - use `<button>` with `aria-expanded` and `aria-controls`;
  - Escape closes them and returns focus to the trigger.
- [ ] Target size is ≥ 24×24 CSS px (2.5.8).
- [ ] No functionality that depends on hover or drag only (2.5.7).

## Content
- [ ] Images have a meaningful `alt`, or `alt=""` when decorative. Icon-only buttons have an accessible name.
- [ ] Decorative SVGs have `aria-hidden="true" focusable="false"`.
- [ ] Links make sense out of context, or have `aria-label` / visually-hidden text.
- [ ] Forms have a `<label>` for every input. Errors are announced and associated (`aria-describedby`).

## Visual
- [ ] Text contrast is ≥ 4.5:1 (≥ 3:1 for large text and UI components), checked for the `theme.json` palette pairs actually used, including style variations.
- [ ] Reflow works at 320px wide, and text spacing overrides don't break the layout.
- [ ] `prefers-reduced-motion` is respected. Nothing flashes more than 3 times a second.
- [ ] Colour isn't the only way information is conveyed.

## Verify
- [ ] The axe Playwright scan shows no `serious` or `critical` violations on the affected templates.
- [ ] A manual keyboard pass of the changed UI, described in the review.
