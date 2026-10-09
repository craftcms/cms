# `craft-input-money` accessibility

Extends `craft-input`; see `input.a11y.md` for label, help text, validation and focus requirements.

The subclass is a masked `type="text"` field with `inputmode="decimal"`, a currency prefix and a clear button.

## Requirements

### Semantics

- [x] The currency prefix shows the ISO code in text beside the symbol, such as `(EUR) €`, so an ambiguous symbol like `$` isn't the only cue. Per WCAG 3.3.2 (Labels or Instructions). Verified by `masks values using the currency locale` in `input-money.test.ts`.
- [ ] The currency is part of the native input's accessible name or description. Per WCAG 1.3.1 (Info and Relationships). Known gap: the prefix is rendered in the shadow root with no `data-label` or `data-description`, so it can't be referenced from the input and isn't announced with it.
- [ ] The clear button is a native button named "Clear". Per WCAG 4.1.2 (Name, Role, Value). Not yet verified. `clears its value` in `input-money.test.ts` clicks it but doesn't check its name.

### Forms and validation

- [x] The mask uses the locale's own decimal and group separators, so the expected format matches what the reader's locale writes. Per WCAG 3.3.2 (Labels or Instructions). Verified by `masks values using the currency locale` in `input-money.test.ts`.
- [ ] Reformatting by the mask, as the value is typed or on blur, changes only the value and never moves focus or the context. Per WCAG 3.2.2 (On Input). Not yet verified.
- [ ] A character the mask rejects is identified in text. Per WCAG 3.3.1 (Error Identification). Known gap: the mask drops it silently, with no message and nothing announced.

### Keyboard and focus

- [ ] After the clear button empties the field and hides itself, focus moves to the native input rather than being lost. Per WCAG 2.4.3 (Focus Order). Not yet verified. `clears its value` in `input-money.test.ts` checks the value and the hidden button, not focus.
- [ ] The clear button is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified. It renders at `size="small"`.

### Visual

- [ ] The mask's placeholder value, drawn in `--c-text-quiet` while the field is empty, reaches 4.5:1 against the input fill. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): name the currency in the label or help text when `show-currency="false"`, or when the field must be understood without the prefix. See [Accessibility](./InputMoney.mdx#accessibility).
- 3.3.2 (Labels or Instructions): say in help text how the amount will be formatted when the locale's conventions may not be the ones a reader expects. See [Accessibility](./InputMoney.mdx#accessibility).
