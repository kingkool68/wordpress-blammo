# Review checklist and finding format

## Finding format (all reviewers)
```
[severity] path/to/file.php:42 — one-line title
  Failure scenario: concrete input/state → wrong output, crash, exploit or barrier
  Fix: specific suggested change
```
| Severity | Meaning | Must fix before PR? |
|---|---|---|
| `blocking` | Security hole, data loss, fatal error, WCAG A failure, broken build or tests | Yes |
| `major` | Real bug in a plausible scenario, WCAG AA failure, missing required tests, convention violation that will spread | Yes |
| `minor` | Edge case, maintainability, small docs drift | Optional; list in PR |
| `nit` | Style or preference | No |

Drop any finding without a concrete failure scenario. End with a one-line verdict: `APPROVE`, `APPROVE WITH NITS` or `CHANGES REQUIRED`.

## General checklist
- [ ] Does the change do what the prompt asked, and nothing unrelated?
- [ ] Does it follow `conventions.md` (prefixes, class pattern, `action_`/`filter_` naming, no Yoda)?
- [ ] Hooks: correct hook, priority and arg count? Does it run too early, e.g. before `init` or `wp_abilities_api_init`?
- [ ] Null and empty handling: missing post, empty attribute, no thumbnail, no menu assigned?
- [ ] Is every output in `render.php`, patterns and styleguide pages escaped?
- [ ] Do `render.php` files avoid declaring functions/classes (two instances of the block on one page must not fatal)?
- [ ] Do the tests cover the behaviour and the escaping, and do they actually fail without the change?
- [ ] Does it degrade without a fatal when `build/` is missing or wp-styleguide isn't active?
- [ ] Do design values come from `theme.json` presets rather than new hard-coded SCSS values?
- [ ] Are template/part changes valid block markup, and was the result checked with site-editor customizations reset?
- [ ] Are the docs updated (`status.md` plus any changed convention)?
- [ ] No debug leftovers (`var_dump`, `error_log`, `console.log`), and no secrets.
