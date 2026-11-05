# `craft-input-handle` accessibility

Extends `craft-input`; see `input.a11y.md` for label, help text, validation and focus requirements.

The subclass sets the value in a monospace face and turns the browser's `autocorrect` and `autocapitalize` off, so a typed handle isn't rewritten. It doesn't generate its value from another field, has no lock or edit toggle, and doesn't restrict or strip characters.

## Requirements

### Reflow and zoom

- [ ] The monospace value is sized relative to the base (`0.9em`), so it scales with the text size and isn't clipped at 200% zoom. Per WCAG 1.4.4 (Resize Text). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): say in help text which characters a valid handle may contain, since the field doesn't restrict them. See [Accessibility](./InputHandle.mdx#accessibility).
- 3.2.2 (On Input): if a script fills the handle from another field, keep focus where it is, and stop overwriting the handle once someone has edited it. See [Accessibility](./InputHandle.mdx#accessibility).
- 3.3.1 (Error Identification): reject invalid characters with a validator message rather than stripping them silently. See [Accessibility](./InputHandle.mdx#accessibility).
