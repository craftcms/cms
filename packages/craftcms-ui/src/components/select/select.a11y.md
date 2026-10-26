# `craft-select` accessibility

Shares `HasLabel` with `craft-input`; see `input.a11y.md` for label, help text and error requirements. This file covers only what the select adds.

The control is the native `<select>` in the `input` slot, so the browser supplies its role, keyboard model, type-ahead and popup. The component draws only the field chrome and a chevron around it.

**Not for:**

- Typing to filter a long list of options. Use `craft-combobox`.
- Options that need more than text, such as an icon or a status. Use `craft-select-rich`.
- A few options that should all stay visible. Use `craft-radio-group`.
- Turning one setting on or off. Use `craft-switch` or `craft-checkbox`.
- Running actions rather than choosing a value. Use `craft-action-menu`.

## Requirements

### Semantics

- [x] The control is the slotted native `<select>` with its native `<option>` elements, so it keeps the native combobox role, value and popup. Per WCAG 4.1.2 (Name, Role, Value). Verified by `adopts the slotted select` in `select.test.ts`.
- [x] The label is a `<label>` whose `for` points at the native `<select>`, so the visible label is its accessible name. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `wires the label to the control` in `select.test.ts`.
- [x] `disabled` disables the native `<select>`, so it leaves the Tab order and is announced as unavailable. Per WCAG 4.1.2 (Name, Role, Value). Verified by `disables the native control` in `select.test.ts`.
- [ ] Native `<optgroup>` labels and `disabled` options are passed through untouched, so groups and unavailable options are announced by the browser. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] The chevron is a `craft-icon` with no `label`, so it stays `aria-hidden` and out of the name. Per WCAG 1.1.1 (Non-text Content). Not yet verified.
- [ ] No custom popup or listbox is rendered; choosing an option uses the browser's own picker. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.

### Keyboard and focus

- [ ] Focus lands on the native `<select>`, and the chevron doesn't intercept clicks or take a tab stop. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] A keyboard-focused select shows the global focus ring at 3:1 against the surface, in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified.

### Visual

- [ ] The select's border reaches 3:1 against the surface, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] In forced colors mode the border, chevron and focus ring stay visible. Not yet verified.

### Reflow and zoom

- [ ] The select fills its container rather than a fixed width, and the selected text and control height scale with the text size, so nothing is clipped at a 320px viewport or 200% zoom. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): give the select a visible label that names what is being chosen. See [Accessibility](./Select.mdx#accessibility).
- 1.3.1 (Info and Relationships): group related options with `<optgroup label="…">` rather than with indented or decorated option text. See [Accessibility](./Select.mdx#accessibility).
- 3.2.2 (On Input): don't submit a form or navigate away when the value changes. See [Accessibility](./Select.mdx#accessibility).
