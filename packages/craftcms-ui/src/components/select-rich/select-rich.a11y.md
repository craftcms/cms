# `craft-select-rich` accessibility

Follows the WAI-ARIA APG [Select-Only Combobox](https://www.w3.org/WAI/ARIA/apg/patterns/combobox/examples/combobox-select-only/) example, with the deviations recorded below. Lion doesn't render a `role="combobox"` element. The invoker is a `role="button"` with `aria-haspopup="listbox"` and `aria-expanded`, and opening the popup moves focus to a separate `role="listbox"` that tracks the highlighted option with `aria-activedescendant`. That is the structure of APG's older collapsible dropdown listbox.

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

- [ ] The control is exposed as a combobox with `aria-expanded`, `aria-controls` and `aria-activedescendant`, as in the example. Per WCAG 4.1.2 (Name, Role, Value). Known deviation: the invoker is a button with `aria-haspopup="listbox"` and no `aria-controls`, and `aria-activedescendant` sits on the listbox, which takes focus when open.
- [ ] The invoker's `aria-expanded` follows the open state. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] The invoker is named by the label and the selected option together (`aria-labelledby` lists the label and the invoker itself), so the current value is announced with the question. Per WCAG 4.1.2 (Name, Role, Value) and 1.3.1 (Info and Relationships). Not yet verified.
- [ ] The listbox is named by the field's label. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] Help text and validation messages are referenced by the invoker's `aria-describedby`, and the invoker carries `aria-invalid` while an error is shown. Per WCAG 1.3.1 (Info and Relationships) and 3.3.1 (Error Identification). Not yet verified.
- [ ] The chevron is a `craft-icon` with no `label`, so it stays `aria-hidden` and out of the name. Per WCAG 1.1.1 (Non-text Content). Not yet verified.
- [ ] With `disabled`, the invoker carries `aria-disabled="true"`, leaves the Tab order and doesn't open. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] With a single option, the invoker keeps a role. Per WCAG 4.1.2 (Name, Role, Value). Known gap: Lion removes the invoker's `role`, `aria-haspopup` and `aria-expanded` when there's only one option, but the element stays focusable, so it's announced with no role.

### Keyboard and focus

- [ ] Enter and Space on the invoker open the listbox. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] Down Arrow and Up Arrow on the closed invoker open the listbox. Per WCAG 2.1.1 (Keyboard). Known deviation: on Windows and Linux, Lion's `interactionMode` makes the arrow keys change the value without opening the listbox. They open it only on macOS.
- [ ] Opening the listbox highlights the selected option. Per WCAG 2.4.3 (Focus Order). Not yet verified.
- [ ] In the open listbox, Up Arrow and Down Arrow move the highlight, and Home and End jump to the first and last option, skipping disabled options. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] Type-ahead moves to the next option starting with the typed characters, in the open listbox and on the closed invoker. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [x] Type-ahead ignores keys pressed with Meta or Ctrl, so a shortcut such as ⌘S doesn't change the value. Per WCAG 3.2.2 (On Input). Verified by `leaves keyboard shortcuts out of type-ahead` in `select-rich.test.ts`, which covers Meta and Ctrl but not Alt.
- [ ] Enter and Space select the highlighted option, close the listbox and return focus to the invoker. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Not yet verified.
- [ ] Escape closes the listbox without changing the value and returns focus to the invoker. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Not yet verified.
- [ ] Tab from the open listbox closes it and moves focus on, with no keyboard trap. Per WCAG 2.1.2 (No Keyboard Trap). Not yet verified.
- [ ] Closing the listbox by clicking elsewhere leaves focus where the click put it. Per WCAG 3.2.1 (On Focus). Not yet verified. Lion's hide handler always focuses the invoker.
- [ ] A keyboard-focused invoker and the focused listbox each show a visible indicator at 3:1 against the surface, in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] The highlighted option scrolls into view inside the listbox, and the open listbox isn't covered by other content. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)). Not yet verified.

### Visual

- [ ] The invoker's border and the listbox's border reach 3:1 against the surface, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] In forced colors mode the invoker border, focus indicators and listbox border stay visible. Not yet verified.

### Reflow and zoom

- [ ] The invoker never grows past its container at a 320px viewport. Per WCAG 1.4.10 (Reflow). Known gap: `_alignInvokerWidth` sets the invoker's `min-width` to the widest option's width in pixels, so a long option pushes the control past a narrow container.
- [ ] The full selected value stays readable at a 320px viewport and at 200% zoom. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Known gap: the invoker's content is kept to one line and truncated with an ellipsis.
- [ ] The listbox opens below the invoker, scrolls its own options within a maximum height, and stays within the viewport at 320px. Per WCAG 1.4.10 (Reflow). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): give the select a visible label that names what is being chosen. See [Accessibility](./SelectRich.mdx#accessibility).
- 1.1.1 (Non-text Content): hide decorative artwork in options, and give informative artwork a text alternative. See [Accessibility](./SelectRich.mdx#accessibility).
- 1.4.1 (Use of Color): an option's color, such as a status dot, only reinforces its text label. See [Accessibility](./SelectRich.mdx#accessibility).
