# `craft-input-time` accessibility

Extends `craft-input`; see `input.a11y.md` for label, help text, validation and focus requirements.

The control is the browser's own `<input type="time">`. There is no custom listbox or combobox, so the browser supplies the typed time segments, their keyboard, and any picker. The picker button is left unstyled, so it falls under 2.5.8's user-agent exception.

**Not for:**

- A date and a time that describe one moment: use `craft-input-date-time`.

## Requirements

### Semantics

- [x] The native input is `type="time"`, so the time can always be typed segment by segment as well as picked, in the browser's own 12- or 24-hour format. Per WCAG 2.1.1 (Keyboard) and 4.1.2 (Name, Role, Value). Verified by `uses native constraints and rejects disabled ranges` in `input-time.test.ts`.
- [x] `minute-increment` is set as the native `step`, in seconds, so arrow-key stepping and native validation follow it. Per WCAG 1.3.1 (Info and Relationships). Verified by `uses native constraints and rejects disabled ranges` in `input-time.test.ts`.
- [ ] `min` and `max` are set on the native input. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.

### Forms and validation

- [x] A time inside a `disabled-time-ranges` range sets a custom validity message on the native input. Per WCAG 3.3.1 (Error Identification). Verified by `uses native constraints and rejects disabled ranges` in `input-time.test.ts`.
- [ ] The unavailable-time error is identified to everyone, not only in the browser's submit-time bubble. Per WCAG 3.3.1 (Error Identification) and 4.1.2 (Name, Role, Value). Known gap: the message isn't added to the `feedback` slot, and `craft-input` still writes `aria-invalid="false"` on the native input while it's invalid.
- [ ] The "This time is unavailable." message is translated with the rest of the interface. Per WCAG 3.1.2 (Language of Parts). Known gap: the string is hard-coded rather than passed through `t()`.

## Depends on usage

- 3.3.2 (Labels or Instructions): name the unavailable times, the bounds, and any rounding in help text, since they're only enforced after a time is chosen. See [Accessibility](./InputTime.mdx#accessibility).
