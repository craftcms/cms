# `craft-input-copy` accessibility

Extends `craft-input`; see `input.a11y.md` for label, help text, validation and focus requirements.

The copy button is a `craft-copy-button`; see `craft-copy-button` for its own name, focus and copy announcement. This file covers how the field uses it.

## Requirements

### Semantics

- [x] The field is always read-only, and stays read-only if `readOnly` is set to `false` at runtime, so the value is focusable and selectable but not editable. Per WCAG 4.1.2 (Name, Role, Value). Verified by `is always read-only` and `stays read-only even if readOnly is set to false` in `input-copy.test.ts`. The tests check the host; the native input's `readonly` is covered in `input.a11y.md`.
- [x] The copy button is a direct child in the `suffix` slot, so it's a separate control from the input and isn't part of its accessible name or description. Per WCAG 1.3.1 (Info and Relationships). Verified by `renders a copy button in the suffix slot` and `places the copy button in the suffix slot` in `input-copy.test.ts`.
- [ ] The copy button's accessible name says what it copies. Per WCAG 2.4.6 (Headings and Labels) and 4.1.2 (Name, Role, Value). Known gap: the field passes only `tooltip-label="Copy"`, so every copy button is named the same and none includes the field's label.
- [ ] A disabled field disables its copy button. Per WCAG 4.1.2 (Name, Role, Value). Known gap: `disabled` isn't passed to the copy button, so it stays enabled, in the Tab order and able to copy.

### Copy behavior

- [ ] A successful or failed copy is announced. Per WCAG 4.1.3 (Status Messages). Known gap: the field adds no announcement of its own, and `craft-copy-button` only changes its tooltip and icon, so the result isn't announced.

### Keyboard and focus

- [ ] Tab order is the input, then the copy button, and focus stays on the button after copying. Per WCAG 2.4.3 (Focus Order). Not yet verified.
- [ ] The copy button's focus ring isn't clipped by the input's container. Per WCAG 2.4.7 (Focus Visible). Not yet verified. The container's `overflow` is set to `visible` for the tooltip.

### Visual

- [ ] The copy button is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified.

## Depends on usage

- 1.3.1 (Info and Relationships): when `copy-value` differs from the displayed value, say so in help text, so it's clear the clipboard gets more than is shown. See [Accessibility](./InputCopy.mdx#accessibility).
