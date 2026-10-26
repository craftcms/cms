# `craft-input` accessibility

The shared text-field requirements for `craft-input` and every control that extends it (`craft-input-color`, `-copy`, `-date`, `-handle`, `-money`, `-password` and `-time`). `craft-textarea` and `craft-input-file` share the `HasLabel` mixin and Lion's form-control wiring without extending `CraftInput`. Inside `craft-field`, the field draws the label, required indicator and instructions; see `field.a11y.md`.

Lion's `FormControlMixin` does the label, help text and feedback wiring. `HasLabel` itself only reflects a `has-label` attribute so the label is spaced from the control when there's one to space, and no gap is left when there isn't.

**Not for:**

- Choosing from a fixed set of values: use `craft-select` or `craft-combobox`.
- Prose that runs past a line: use `craft-textarea`.

## Requirements

### Semantics

- [x] The control is a native `<input>` in the light DOM, and the `label` attribute or a slotted `label` is a `<label>` whose `for` points at it, so the input's accessible name is the visible label. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `renders a native input with the label wired to it` in `input.test.ts`.
- [ ] The label is also added to the native input's `aria-labelledby`, and text in a `prefix`, `suffix`, `before` or `after` element marked `data-label` joins it. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.
- [x] `type` is passed to the native input, so `email`, `number`, `url` and the rest keep their native role, keyboard and validation. Per WCAG 4.1.2 (Name, Role, Value). Verified by `passes a type through to the native input` in `input.test.ts`.
- [ ] `autocomplete` is passed to the native input. Per WCAG 1.3.5 (Identify Input Purpose). Not yet verified.
- [x] `disabled` disables the native input, so it leaves the Tab order and is announced as unavailable. Per WCAG 4.1.2 (Name, Role, Value). Verified by `disables the native input` in `input.test.ts`.
- [ ] `readonly` sets the native `readonly` attribute, so the value stays focusable and is announced as read-only. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] Content in the `prefix` and `suffix` slots stays out of the accessible name and description unless it's marked `data-label` or `data-description`. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.

### Forms and validation

- [ ] Help text, from the `help-text` attribute or slot, is referenced by the native input's `aria-describedby`. Per WCAG 1.3.1 (Info and Relationships) and 3.3.2 (Labels or Instructions). Not yet verified.
- [ ] A `Required` validator sets `aria-required="true"` on the native input, and removes it when the validator is removed. Per WCAG 3.3.2 (Labels or Instructions) and 4.1.2 (Name, Role, Value). Not yet verified.
- [x] While an error is shown, the native input carries `aria-invalid="true"`, and it goes back to `false` once the error clears. An `aria-invalid` set on the host overrides the validation state, and removing it hands control back. Per WCAG 3.3.1 (Error Identification) and 4.1.2 (Name, Role, Value). Verified by `synchronizes aria-invalid with the native input` and `preserves validation-managed aria-invalid without an override` in `input.test.ts`.
- [ ] Validation messages in the `feedback` slot are referenced by the native input's `aria-describedby`. Per WCAG 3.3.1 (Error Identification). Not yet verified.
- [ ] Validation messages are announced when they appear: the `feedback` slot is `aria-live="polite"` while focus is in the control and `assertive` once it leaves. Per WCAG 4.1.3 (Status Messages). Not yet verified.

### Keyboard and focus

- [ ] Focus lands on the native input, and Tab order follows the DOM: `before`, `prefix`, the input, `suffix`, `after`. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Not yet verified.
- [ ] A keyboard-focused input shows the global focus ring at 3:1 against the surface, in the light and dark themes. With a `prefix` or `suffix` the ring is drawn inset so the adjoining chrome doesn't cover it. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified.

### Visual

- [ ] The input's border, and the borders of the `prefix` and `suffix` chrome, reach 3:1 against the surface and the input fill, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified. The default `--c-form-control-border-color` is close to the threshold.
- [ ] The error border (`--c-color-danger-border-loud`) reaches 3:1 against the surface. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [x] Placeholder text (`--c-form-control-placeholder`, the quiet text color) reaches 4.5:1 against the surface, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Verified by `sets the placeholder at 4.5:1 against the surface in the %s theme` in `input.browser.test.ts`.
- [ ] Help text (`--c-text-quiet`) and the value text reach 4.5:1 against their backgrounds, including the input fill, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified.
- [ ] In forced colors mode the input's border and focus ring stay visible. Not yet verified.

### Reflow and zoom

- [x] A `maxlength` shrinks the control to its expected width but never past its container, so a long limit doesn't push the page sideways. Per WCAG 1.4.10 (Reflow). Verified by `shrinks to fit a short maxlength` and `doesn’t outgrow its container for a long maxlength` in `input.browser.test.ts`.
- [ ] The control's text and height scale with the text size, so the value isn't clipped at 200% zoom. Per WCAG 1.4.4 (Resize Text). Not yet verified.
- [ ] Wide content in `prefix` or `suffix` doesn't push the control past its container at a 320px viewport. Per WCAG 1.4.10 (Reflow). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): give every input a visible label, through `label`, the `label` slot or `craft-field`, and don't use `placeholder` as the label. See [Accessibility](./Input.mdx#accessibility).
- 2.5.3 (Label in Name): an `aria-label` used instead of a visible label must contain the text shown beside the field. See [Accessibility](./Input.mdx#accessibility).
- 3.3.2 (Labels or Instructions): show which fields are required, with `craft-field`'s `required` or in the label text, alongside the `Required` validator. See [Accessibility](./Input.mdx#accessibility).
- 3.3.3 (Error Suggestion): write validator messages that say how to fix the value. See [Accessibility](./Input.mdx#accessibility).
- 1.4.1 (Use of Color): the error border only reinforces the message, so an invalid input needs a message in `feedback`. See [Accessibility](./Input.mdx#accessibility).
- 1.3.5 (Identify Input Purpose): set `autocomplete` on fields that collect the user's own details. See [Accessibility](./Input.mdx#accessibility).
- 3.3.2 (Labels or Instructions): name the expected format in help text. See [Accessibility](./Input.mdx#accessibility).
- 1.3.1 (Info and Relationships): mark `prefix` or `suffix` text that a screen reader needs, such as a unit, with `data-label` or `data-description`. See [Accessibility](./Input.mdx#accessibility).
- 2.5.8 (Target Size (Minimum)): buttons slotted into `prefix` or `suffix` need a 24×24 CSS px target and an accessible name. See [Accessibility](./Input.mdx#accessibility).
