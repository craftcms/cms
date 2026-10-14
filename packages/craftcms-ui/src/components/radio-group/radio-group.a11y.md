# `craft-radio-group` accessibility

Follows the WAI-ARIA APG [Radio Group](https://www.w3.org/WAI/ARIA/apg/patterns/radio/) pattern, using native radio inputs. With `buttons`, the radios are drawn as a row of joined buttons, but keep the pattern's roles and keyboard behavior.

**Not for:**

- More than one selected option. Use `craft-checkbox-group`.
- Buttons that act immediately rather than set a value, such as switching a view. Use `craft-button-group`, whose buttons stay buttons and are each a tab stop.
- Buttons that switch between views, where one view is always showing. Use `craft-tabs`.

## Requirements

### Semantics

- [x] With `buttons`, each option is still a native radio input, named by its label, so it's announced as a radio with its checked state. Per WCAG 4.1.2 (Name, Role, Value). Verified by `keeps each option a named radio input` in `radio-group.test.ts`.

### Keyboard and focus

- [x] With `buttons`, the group is a single tab stop, and the arrow keys move the selection between the options. Per WCAG 2.1.1 (Keyboard). Verified by `moves the selection with the arrow keys` in `radio-group.browser.test.ts`.
- [x] With `buttons`, the focused option shows a focus ring around its whole button, drawn above its neighbors. Per WCAG 2.4.7 (Focus Visible). Verified by `draws the focus ring around the focused button` in `radio-group.browser.test.ts`.
- [x] With `buttons`, clicking anywhere on an option's button selects it. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `selects an option when its button is clicked` in `radio-group.browser.test.ts`.

### Visual

- [x] With `buttons`, adjacent buttons share one border, and only the outer corners are rounded. Verified by `joins the buttons, rounding only the outer corners` in `radio-group.browser.test.ts`.
- [x] With `buttons`, the selected option is filled, and drawn above its neighbors so its whole border shows. Verified by `fills the selected button and raises it` in `radio-group.browser.test.ts`.
- [x] With `buttons`, an option whose label is only an icon gets a square button at least as large as a control. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `makes an icon-only option a square button` in `radio-group.browser.test.ts`.
- [x] With `buttons`, the selected option is distinguishable from the others in forced colors mode, using system colors. Verified by `distinguishes the selected button in forced colors mode` in `radio-group.browser.test.ts`.
