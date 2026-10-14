# `craft-textarea` accessibility

Shares `HasLabel` with `craft-input`; see `input.a11y.md` for label, help text and error requirements. Built on Lion's textarea rather than `CraftInput`, so `input.test.ts` doesn't cover it; this file lists what it adds and what its own tests verify.

## Requirements

### Semantics

- [x] The control is a native `<textarea>`, so it's announced as a multi-line text field and Enter adds a line rather than submitting the form. Per WCAG 4.1.2 (Name, Role, Value). Verified by `renders a native textarea, not an input` in `textarea.test.ts`.
- [x] The `label` attribute or a slotted `label` is a `<label>` whose `for` points at the textarea. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `wires the label to the control` in `textarea.test.ts`.
- [x] `disabled` disables the native textarea. Per WCAG 4.1.2 (Name, Role, Value). Verified by `disables the native control` in `textarea.test.ts`.
- [ ] `readonly` sets the native `readonly` attribute, so the value stays focusable and selectable. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified. The `Readonly` story has no `play()` assertion.

### Forms and validation

- [ ] Help text and validation messages are referenced by the textarea's `aria-describedby`. Per WCAG 1.3.1 (Info and Relationships) and 3.3.1 (Error Identification). Not yet verified.
- [ ] While an error is shown, the textarea carries `aria-invalid="true"`. Lion's `ValidateMixin` sets it here, not `CraftInput`'s sync, so an `aria-invalid` on the host doesn't override it. Per WCAG 3.3.1 (Error Identification) and 4.1.2 (Name, Role, Value). Not yet verified.

### Reflow and zoom

- [ ] The textarea grows with its content up to `max-rows` (6 by default), then scrolls, so no text is clipped at 200% zoom or a 320px viewport. Per WCAG 1.4.4 (Resize Text) and 1.4.10 (Reflow). Not yet verified.
- [ ] The resize handle stays available, and a manual resize turns auto-grow off so the next keystroke doesn't undo it. Per WCAG 1.4.4 (Resize Text). Not yet verified. The drag is still capped at the `max-rows` height.

## Depends on usage

- 3.3.2 (Labels or Instructions): if the value has a length limit, say so in help text, since the textarea shows no character count. See [Accessibility](./Textarea.mdx#accessibility).
