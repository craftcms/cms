# `craft-copy-attribute` accessibility

A `craft-copy-button` that shows the value it copies, in a monospace chip. See `copy-button.a11y.md` for the button's own name, status and focus requirements. This file covers only what the wrapper adds.

## Requirements

### Semantics

- [x] The button's visible text is the value it copies, so its accessible name starts with what's copied. Per WCAG 2.4.6 (Headings and Labels) and 2.5.3 (Label in Name). Verified by `shows the value and hands it to the copy button` in `copy-attribute.test.ts`.
- [x] Each instance's copy button gets an id, so its tooltip can reference it. Per WCAG 4.1.2 (Name, Role, Value). Verified by `gives each instance an id, so its tooltip can be referenced` in `copy-attribute.test.ts`.
- [ ] `disabled` disables the copy button. Per WCAG 4.1.2 (Name, Role, Value). Known gap: the property is never passed to `craft-copy-button`, so a disabled copy attribute stays in the Tab order and still copies.

### Visual

- [ ] The chip is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified. Its `min-height` is 20px.
- [ ] A keyboard-focused chip shows a visible indicator at 3:1 against its surface. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified. The chip's own `:focus` style only darkens its border and text.
- [ ] The value text meets 4.5:1 against the chip at rest, on hover and on focus, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified.

## Depends on usage

- 4.1.3 (Status Messages): announce the result from `craft-copy` and `craft-error`, since the copy button doesn't yet. See [Accessibility](./CopyAttribute.mdx#accessibility).
- 2.4.6 (Headings and Labels): use it only where the value says what it is, such as a field handle, since the button can't be given another name. See [Accessibility](./CopyAttribute.mdx#accessibility).
