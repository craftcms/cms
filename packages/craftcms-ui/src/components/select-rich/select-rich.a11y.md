# `craft-select-rich` accessibility

Follows the WAI-ARIA APG [Select-Only Combobox](https://www.w3.org/WAI/ARIA/apg/patterns/combobox/examples/combobox-select-only/) example, with the deviations recorded below. The field is the `role="combobox"` element. It keeps focus while the listbox is open and tracks the highlighted option with `aria-activedescendant`.

Shares `HasLabel` with `craft-input`; see `input.a11y.md` for label, help text and error requirements. The options are `craft-option` elements; see `option.a11y.md`.

**Not for:**

- Typing to filter a long list of options. Use `craft-combobox`.
- Plain text options. Use `craft-select`, which keeps the platform picker.
- A few options that should all stay visible. Use `craft-radio-group`.
- Turning one setting on or off. Use `craft-switch` or `craft-checkbox`.
- Running actions rather than choosing a value. Use `craft-action-menu`.

A change that needs any of these is a different pattern. Don't add a mode to `craft-select-rich` for it.

## Requirements

### Semantics

- [x] The field is a combobox with `aria-haspopup="listbox"` and `aria-expanded`, and keeps focus while open, pointing `aria-activedescendant` at the highlighted option. Per WCAG 4.1.2 (Name, Role, Value). Verified by `is a select-only combobox that keeps focus while its listbox is open` in `select-rich.browser.test.ts`. Known deviation: no `aria-controls`, because the listbox is in the select's shadow root and can't be referenced from the field.
- [x] The field's `aria-expanded` follows the open state. Per WCAG 4.1.2 (Name, Role, Value). Verified by `is a select-only combobox that keeps focus while its listbox is open` in `select-rich.browser.test.ts`.
- [x] The field is named by the label, and shows the selected option as its content. Per WCAG 4.1.2 (Name, Role, Value) and 1.3.1 (Info and Relationships). Verified by `is a select-only combobox that keeps focus while its listbox is open` in `select-rich.browser.test.ts`. Not yet verified with screen readers.
- [x] The listbox is named by the field's label. Per WCAG 4.1.2 (Name, Role, Value). Verified by `is a select-only combobox that keeps focus while its listbox is open` in `select-rich.browser.test.ts`.
- [x] Help text and validation messages are referenced by the field's `aria-describedby`, and the field carries `aria-invalid` while `invalid` is set, such as for a value the server rejected. Per WCAG 1.3.1 (Info and Relationships) and 3.3.1 (Error Identification). Verified by `is a select-only combobox that keeps focus while its listbox is open` and `marks the field invalid while `invalid` is set` in `select-rich.browser.test.ts`.
- [ ] The chevron is a `craft-icon` with no `label`, so it stays `aria-hidden` and out of the name. Per WCAG 1.1.1 (Non-text Content). Not yet verified.
- [ ] With `disabled`, the field carries `aria-disabled="true"`, leaves the Tab order and doesn't open. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [x] With a single option, the field keeps its role and is marked `aria-readonly`, with nothing to open. Per WCAG 4.1.2 (Name, Role, Value). Verified by `keeps its role with a single option, read-only with nothing to open` in `select-rich.browser.test.ts`.

### Keyboard and focus

- [x] Enter and Space on the field open the listbox. Per WCAG 2.1.1 (Keyboard). Verified by `opens on %s with the selected option active` in `select-rich.browser.test.ts`.
- [x] Down Arrow and Up Arrow on the closed field open the listbox, on every platform. Per WCAG 2.1.1 (Keyboard). Verified by `opens on %s with the selected option active` in `select-rich.browser.test.ts`.
- [x] Opening the listbox highlights the selected option. Per WCAG 2.4.3 (Focus Order). Verified by `opens on %s with the selected option active` in `select-rich.browser.test.ts`.
- [x] In the open listbox, Up Arrow and Down Arrow move the highlight, stopping at the ends, and Home and End jump to the first and last option, skipping disabled and hidden options. Type-ahead skips hidden options too. Per WCAG 2.1.1 (Keyboard). Verified by `moves past disabled options, stops at the ends, and jumps with Home and End` and `leaves hidden options out of keyboard selection, but still shows one that is selected` in `select-rich.browser.test.ts`.
- [x] Type-ahead selects the first option starting with the typed characters on the closed field, as a native select does, and moves the highlight in the open listbox. Per WCAG 2.1.1 (Keyboard). Verified by `selects by type-ahead while closed and moves the highlight while open` in `select-rich.browser.test.ts`.
- [x] Type-ahead ignores keys pressed with Meta or Ctrl, so a shortcut such as ⌘S doesn't change the value. Per WCAG 3.2.2 (On Input). Verified by `leaves keyboard shortcuts out of type-ahead` in `select-rich.test.ts`, which covers Meta and Ctrl. Alt is ignored too.
- [x] Enter and Space select the highlighted option and close the listbox, with focus staying on the field. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Verified by `selects with Enter, and closes without a change on Escape, keeping focus on the field` in `select-rich.browser.test.ts`.
- [x] Escape closes the listbox without changing the value, with focus staying on the field. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Verified by `selects with Enter, and closes without a change on Escape, keeping focus on the field` in `select-rich.browser.test.ts`.
- [x] Tab from the open listbox closes it and moves focus on, with no keyboard trap. Per WCAG 2.1.2 (No Keyboard Trap). Verified by `closes on Tab and moves focus on` in `select-rich.browser.test.ts`. As in the example, Tab also selects the highlighted option; that part is not yet verified.
- [ ] Closing the listbox by clicking elsewhere leaves focus where the click put it. Per WCAG 3.2.1 (On Focus). Not yet verified.
- [ ] A keyboard-focused field shows a visible indicator at 3:1 against the surface, in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] The highlighted option scrolls into view inside the listbox, and the open listbox isn't covered by other content. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)). Not yet verified.

### Visual

- [ ] The invoker's border and the listbox's border reach 3:1 against the surface, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] In forced colors mode the invoker border, focus indicators and listbox border stay visible. Not yet verified.

### Reflow and zoom

- [x] The invoker never grows past its container at a 320px viewport, even when an option is long. Per WCAG 1.4.10 (Reflow). Verified by `stays within its container and the viewport when an option is long, $expected` in `select-rich.browser.test.ts`.
- [ ] The full selected value stays readable at a 320px viewport and at 200% zoom. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Known gap: the invoker's content is kept to one line and truncated with an ellipsis.
- [x] The listbox stays within the viewport at 320px: it's at least as wide as the invoker, and no wider than 22.5rem unless the invoker is, so long labels and hints truncate. Per WCAG 1.4.10 (Reflow). Verified by `stays within its container and the viewport when an option is long, $expected` in `select-rich.browser.test.ts`.
- [ ] The listbox opens below the invoker and scrolls its own options within a maximum height. Per WCAG 1.4.10 (Reflow). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): give the select a visible label that names what is being chosen. See [Accessibility](./SelectRich.mdx#accessibility).
- 1.1.1 (Non-text Content): hide decorative artwork in options, and give informative artwork a text alternative. See [Accessibility](./SelectRich.mdx#accessibility).
- 1.4.1 (Use of Color): an option's color, such as a status dot, only reinforces its text label. See [Accessibility](./SelectRich.mdx#accessibility).
