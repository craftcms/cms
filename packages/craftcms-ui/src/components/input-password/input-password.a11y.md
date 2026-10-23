# `craft-input-password` accessibility

Extends `craft-input`; see `input.a11y.md` for label, help text, validation and focus requirements.

The subclass adds a reveal button in the `suffix` slot. Its state is carried by a name change ("Show", then "Hide") rather than `aria-pressed`. There's no caps-lock warning.

## Requirements

### Semantics

- [ ] The reveal button is slotted into `suffix` in the light DOM, so it follows the input in the Tab order. Per WCAG 2.4.3 (Focus Order). Not yet verified: `places the reveal button in the suffix slot` in `input-password.test.ts` checks slot assignment, not the Tab sequence.
- [ ] The reveal button's name says what it will do, "Show" or "Hide", and changes with the state. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified. `toggles the input type when the reveal button is clicked` in `input-password.test.ts` checks the type, not the name.
- [ ] The reveal button's name says what it reveals. Per WCAG 2.4.6 (Headings and Labels). Known gap: it's "Show" or "Hide" alone, not "Show password".
- [ ] The reveal button is `type="button"`, so pressing it doesn't submit the form. Per WCAG 3.2.2 (On Input). Not yet verified.

### Authentication

- [x] Pressing the reveal button switches the field between `password` and `text` and back, so the value can be checked without relying on memory. Per WCAG 3.3.8 (Accessible Authentication (Minimum)). Verified by `toggles the input type when the reveal button is clicked` in `input-password.test.ts`.
- [x] `passwordrules` is passed to the native input, so a password manager can generate a password that passes. Per WCAG 3.3.8 (Accessible Authentication (Minimum)). Verified by `forwards password rules to the native input` in `input-password.test.ts`.
- [ ] Paste and password-manager autofill aren't blocked. Per WCAG 3.3.8 (Accessible Authentication (Minimum)). Not yet verified. The component adds no paste or autofill handling.

### Keyboard and focus

- [ ] Focus stays on the reveal button after it's pressed, since the same button is kept across rerenders. Per WCAG 2.4.3 (Focus Order). Not yet verified. `keeps the reveal button slotted across rerenders` in `input-password.test.ts` counts the buttons but doesn't check that the node or focus survives.
- [ ] The reveal button is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified. It renders at `size="small"`.

### Visual

- [ ] The eye icon reaches 3:1 against the input fill and stays visible in forced colors mode. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.

## Depends on usage

- 1.3.5 (Identify Input Purpose): set `autocomplete="current-password"` on sign-in forms and `"new-password"` where a password is set. See [Accessibility](./InputPassword.mdx#accessibility).
- 3.3.8 (Accessible Authentication (Minimum)): don't block paste on the field, and don't ask for a password to be retyped from memory without allowing paste or a password manager. See [Accessibility](./InputPassword.mdx#accessibility).
- 3.3.2 (Labels or Instructions): state password requirements in help text before the field is submitted. See [Accessibility](./InputPassword.mdx#accessibility).
