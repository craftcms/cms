# `craft-textarea` accessibility

Shares `FormControl` with `craft-input`; see `input.a11y.md` for label, help text and error requirements. Built on `NativeField` directly rather than `CraftInput`, so `input.test.ts` doesn't cover it; this file lists what it adds and what its own tests verify.

## Requirements

### Semantics

- [x] The control is a native `<textarea>`, so it's announced as a multi-line text field and Enter adds a line rather than submitting the form. Per WCAG 4.1.2 (Name, Role, Value). Verified by `renders a native textarea, not an input` in `textarea.test.ts`.
- [x] The `label` attribute or a slotted `label` is a `<label>` whose `for` points at the textarea. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `wires the label to the control` in `textarea.test.ts`.
- [x] `disabled` disables the native textarea. Per WCAG 4.1.2 (Name, Role, Value). Verified by `disables the native control` in `textarea.test.ts`.
- [ ] `readonly` sets the native `readonly` attribute, so the value stays focusable and selectable. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified. The `Readonly` story has no `play()` assertion.

### Forms and validation

- [ ] Help text and validation messages are referenced by the textarea's `aria-describedby`. Per WCAG 1.3.1 (Info and Relationships) and 3.3.1 (Error Identification). Not yet verified.
- [ ] While an error is shown, the textarea carries `aria-invalid="true"`, from the host's `invalid` property. Per WCAG 3.3.1 (Error Identification) and 4.1.2 (Name, Role, Value). Not yet verified.

### Reflow and zoom

- [x] The textarea is at least `rows` tall and grows with its content up to `max-rows` (30 by default, or without limit with `none`), then scrolls, so no text is clipped. Per WCAG 1.4.4 (Resize Text) and 1.4.10 (Reflow). Verified by `is as tall as its rows when it holds less`, `grows with its content`, `stops growing at max-rows`, `keeps growing when max-rows is none`, `scrolls the content that doesn’t fit once it stops growing` and `grows as text is typed into it` in `textarea.browser.test.ts`.
- [ ] The resize handle stays available, and a manual resize turns auto-grow off so the next keystroke doesn't undo it. Per WCAG 1.4.4 (Resize Text). Not yet verified. The drag is still capped at the `max-rows` height.

## Depends on usage

- 3.3.2 (Labels or Instructions): if the value has a length limit, say so in help text, since the textarea shows no character count. See [Accessibility](./Textarea.mdx#accessibility).
