# `craft-radio-group` accessibility

Follows the WAI-ARIA APG [Radio Group](https://www.w3.org/WAI/ARIA/apg/patterns/radio/) pattern. The options are `craft-radio`s, covered by `radio.a11y.md`.

The group adds `role="radiogroup"` and the label, help text and feedback wiring through Lion's `FormGroupMixin`. The keyboard behavior comes from the browser: each option is a native `<input type="radio">`, and radios that share a `name` form one group. `HasLabel` only reflects `has-label` for spacing; see `input.a11y.md`.

**Not for:**

- Choices where several options can be on at once. Use `craft-checkbox-group`.
- A long list of options. Use `craft-select` or `craft-select-rich`.
- Switching between views. Use `craft-tabs`.
- A row of buttons that each run an action. Use `craft-button-group` without a `name`.

## Requirements

### Semantics

- [ ] The host carries `role="radiogroup"`, and its label (the `label` attribute or a slotted `label`) is referenced by the host's `aria-labelledby`, so the group has an accessible name. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Not yet verified.
- [x] Every option's native input carries the group's `name`, including when a server-rendered group takes its name from its inputs, so the browser treats the options as one group. Per WCAG 1.3.1 (Info and Relationships). Verified by `adopts the group name from the slotted inputs instead of wiping theirs` and `respects an explicit host name` in `radio-group.test.ts`.
- [x] A server-rendered checked option becomes the group's value on upgrade, so the selection shown matches the one posted. Per WCAG 4.1.2 (Name, Role, Value). Verified by `aggregates SSR checked state into the group modelValue` in `radio-group.test.ts`.
- [ ] `disabled` disables every option, so they leave the Tab order and are announced as unavailable. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.

### Keyboard and focus

- [ ] The group is one Tab stop: Tab moves to the checked option, or to the first option when none is checked, and the next Tab leaves the group. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Not yet verified.
- [ ] Arrow keys move focus to the next or previous option and select it, wrapping at both ends and skipping disabled options. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] Space selects the focused option when none is checked. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] In `thumbnails` mode, the visual order of the tiles matches the DOM order that the arrow keys follow. Per WCAG 1.3.2 (Meaningful Sequence) and 2.4.3 (Focus Order). Not yet verified.

### Forms and validation

- [ ] Help text, from the `help-text` attribute or slot, is referenced by the host's `aria-describedby`. Per WCAG 1.3.1 (Info and Relationships) and 3.3.2 (Labels or Instructions). Not yet verified.
- [ ] A `Required` validator sets `aria-required="true"` on the host, and removes it when the validator is removed. Per WCAG 3.3.2 (Labels or Instructions) and 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] While an error is shown, the host carries `aria-invalid="true"`, and the message in the `feedback` slot is referenced by its `aria-describedby`. Per WCAG 3.3.1 (Error Identification). Not yet verified.
- [ ] Validation messages are announced when they appear. Per WCAG 4.1.3 (Status Messages). Not yet verified.

### Reflow and zoom

- [ ] In `thumbnails` mode, the tiles fit a 320px viewport without horizontal scrolling. Per WCAG 1.4.10 (Reflow). Known gap: the tiles are laid out with `grid-auto-flow: column`, which never wraps, so a row of tiles wider than its container overflows.
- [ ] Option labels wrap rather than clip at 200% zoom and at a 320px viewport. Per WCAG 1.4.4 (Resize Text) and 1.4.10 (Reflow). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): give the group a visible label that names the question. See [Accessibility](./RadioGroup.mdx#accessibility).
- 3.3.2 (Labels or Instructions): show that a choice is required, with `craft-field`'s `required` or in the label text, alongside the `Required` validator. See [Accessibility](./RadioGroup.mdx#accessibility).
- 3.3.3 (Error Suggestion): write validator messages that say what to choose. See [Accessibility](./RadioGroup.mdx#accessibility).
- 3.2.2 (On Input): don't submit the form or navigate when the selection changes. See [Accessibility](./RadioGroup.mdx#accessibility).
