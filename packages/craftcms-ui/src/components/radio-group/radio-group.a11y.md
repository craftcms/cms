# `craft-radio-group` accessibility

Follows the WAI-ARIA APG [Radio Group](https://www.w3.org/WAI/ARIA/apg/patterns/radio/) pattern. The options are `craft-radio`s, covered by `radio.a11y.md`. With `buttons`, the radios are drawn as a row of joined buttons, and with `thumbnails` as illustrated tiles, but either way they keep the pattern's roles and keyboard behavior.

The group adds `role="radiogroup"` and the label, help text and feedback wiring through Lion's `FormGroupMixin`. The keyboard behavior comes from the browser: each option is a native `<input type="radio">`, and radios that share a `name` form one group. `HasLabel` only reflects `has-label` for spacing; see `input.a11y.md`.

**Not for:**

- Choices where several options can be on at once. Use `craft-checkbox-group`.
- A long list of options. Use `craft-select` or `craft-select-rich`.
- Buttons that switch between views, where one view is always showing. Use `craft-tabs`.
- Buttons that act immediately rather than set a value, or a row of buttons that each run an action. Use `craft-button-group`, whose buttons stay buttons and are each a Tab stop.

## Requirements

### Semantics

- [ ] The host carries `role="radiogroup"`, and its label (the `label` attribute or a slotted `label`) is referenced by the host's `aria-labelledby`, so the group has an accessible name. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Not yet verified.
- [x] Every option's native input carries the group's `name`, including when a server-rendered group takes its name from its inputs, so the browser treats the options as one group. Per WCAG 1.3.1 (Info and Relationships). Verified by `adopts the group name from the slotted inputs instead of wiping theirs` and `respects an explicit host name` in `radio-group.test.ts`.
- [x] A server-rendered checked option becomes the group's value on upgrade, so the selection shown matches the one posted. Per WCAG 4.1.2 (Name, Role, Value). Verified by `aggregates SSR checked state into the group modelValue` in `radio-group.test.ts`.
- [ ] `disabled` disables every option, so they leave the Tab order and are announced as unavailable. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [x] With `buttons`, each option is still a native radio input, named by its label, so it's announced as a radio with its checked state. Per WCAG 4.1.2 (Name, Role, Value). Verified by `keeps each option a named radio input` in `radio-group.test.ts`.

### Keyboard and focus

- [ ] The group is one Tab stop: Tab moves to the checked option, or to the first option when none is checked, and the next Tab leaves the group. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Not yet verified.
- [ ] Arrow keys move focus to the next or previous option and select it, wrapping at both ends and skipping disabled options. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] Space selects the focused option when none is checked. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [x] In `thumbnails` mode, the visual order of the tiles matches the DOM order that the arrow keys follow, including once they wrap. Per WCAG 1.3.2 (Meaningful Sequence) and 2.4.3 (Focus Order). Verified by `lays wrapped tiles out in the order the arrow keys follow` in `radio-group.browser.test.ts`.
- [x] In `thumbnails` mode, the focused option shows a focus ring around its illustration. Per WCAG 2.4.7 (Focus Visible). Verified by `draws the focus ring around the focused tile illustration` in `radio-group.browser.test.ts`.
- [x] In `thumbnails` mode, clicking an option's illustration selects it. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `selects an option when its illustration is clicked` in `radio-group.browser.test.ts`.
- [x] With `buttons`, the group is a single tab stop, and the arrow keys move the selection between the options. Per WCAG 2.1.1 (Keyboard). Verified by `moves the selection with the arrow keys` in `radio-group.browser.test.ts`.
- [x] With `buttons`, the focused option shows a focus ring around its whole button, drawn above its neighbors. Per WCAG 2.4.7 (Focus Visible). Verified by `draws the focus ring around the focused button` in `radio-group.browser.test.ts`.
- [x] With `buttons`, clicking anywhere on an option's button selects it. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `selects an option when its button is clicked` in `radio-group.browser.test.ts`.

### Forms and validation

- [ ] Help text, from the `help-text` attribute or slot, is referenced by the host's `aria-describedby`. Per WCAG 1.3.1 (Info and Relationships) and 3.3.2 (Labels or Instructions). Not yet verified.
- [ ] A `Required` validator sets `aria-required="true"` on the host, and removes it when the validator is removed. Per WCAG 3.3.2 (Labels or Instructions) and 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] While an error is shown, the host carries `aria-invalid="true"`, and the message in the `feedback` slot is referenced by its `aria-describedby`. Per WCAG 3.3.1 (Error Identification). Not yet verified.
- [ ] Validation messages are announced when they appear. Per WCAG 4.1.3 (Status Messages). Not yet verified.

### Reflow and zoom

- [x] In `thumbnails` mode, the tiles fit a 320px wide container without horizontal scrolling: once the joined row doesn't fit, they come apart and wrap. Per WCAG 1.4.10 (Reflow). Verified by `wraps the tiles into separate tiles when the row does not fit` and `fits the tiles in a 320px wide container without scrolling sideways` in `radio-group.browser.test.ts`.
- [ ] Option labels wrap rather than clip at 200% zoom and at a 320px viewport. Per WCAG 1.4.4 (Resize Text) and 1.4.10 (Reflow). Not yet verified.

### Visual

- [x] With `buttons`, adjacent buttons share one border, and only the outer corners are rounded. Verified by `joins the buttons, rounding only the outer corners` in `radio-group.browser.test.ts`.
- [x] With `buttons`, the selected option is filled, and drawn above its neighbors so its whole border shows. Verified by `fills the selected button and raises it` in `radio-group.browser.test.ts`.
- [x] With `buttons`, an option whose label is only an icon gets a square button at least as large as a control. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `makes an icon-only option a square button` in `radio-group.browser.test.ts`.
- [x] With `buttons`, the selected option is distinguishable from the others in forced colors mode, using system colors. Verified by `distinguishes the selected button in forced colors mode` in `radio-group.browser.test.ts`.
- [x] In `thumbnails` mode, the selected tile has a thicker border and a bold label, so it differs by more than color. Per WCAG 1.4.1 (Use of Color). Verified by `marks the selected tile with a thicker border and a bold label` in `radio-group.browser.test.ts`.
- [x] In `thumbnails` mode, the selected tile is distinguishable from the others in forced colors mode, using system colors. Verified by `distinguishes the selected tile in forced colors mode` in `radio-group.browser.test.ts`.

## Depends on usage

- 3.3.2 (Labels or Instructions): give the group a visible label that names the question. See [Accessibility](./RadioGroup.mdx#accessibility).
- 3.3.2 (Labels or Instructions): show that a choice is required, with `craft-field`'s `required` or in the label text, alongside the `Required` validator. See [Accessibility](./RadioGroup.mdx#accessibility).
- 3.3.3 (Error Suggestion): write validator messages that say what to choose. See [Accessibility](./RadioGroup.mdx#accessibility).
- 3.2.2 (On Input): don't submit the form or navigate when the selection changes. See [Accessibility](./RadioGroup.mdx#accessibility).
