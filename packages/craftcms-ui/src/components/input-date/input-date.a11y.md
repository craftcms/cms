# `craft-input-date` accessibility

Extends `craft-input`; see `input.a11y.md` for label, help text, validation and focus requirements.

The control is the browser's own `<input type="date">`. There is no custom calendar, so the APG Date Picker Dialog pattern doesn't apply: the browser supplies the typed date segments, their keyboard, and the picker. The picker button is left unstyled, so it falls under 2.5.8's user-agent exception.

**Not for:**

- A date and a time that describe one moment: use `craft-input-date-time`.

## Requirements

### Semantics

- [x] The native input is `type="date"` and shows the bound value, so the date can always be typed segment by segment as well as picked, and is announced in the browser's own date format. Per WCAG 2.1.1 (Keyboard) and 4.1.2 (Name, Role, Value). Verified by `uses the native date input` in `input-date.test.ts`.
- [ ] `min` and `max` are set on the native input, so the browser's picker, stepping and validation honor them. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): when the date is bounded, say the earliest or latest allowed date in help text. See [Accessibility](./InputDate.mdx#accessibility).
