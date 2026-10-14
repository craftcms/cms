# `craft-checkbox` accessibility

Follows the WAI-ARIA APG [Checkbox](https://www.w3.org/WAI/ARIA/apg/patterns/checkbox/) pattern (dual-state), using a native `<input type="checkbox">` in the light DOM. Lion's `ChoiceInputMixin` and `FormControlMixin` wire the label, help text and feedback. `SsrChoiceInputMixin` adopts the state of a server-rendered input. For a group of checkboxes see `checkbox-group.a11y.md`; for a "select all" parent see `checkbox-indeterminate.a11y.md`.

**Not for:**

- An on/off setting that takes effect as soon as it's toggled. Use `craft-switch`.
- Picking exactly one of several options. Use `craft-radio-group`.
- Picking from a long list of options. Use `craft-select-rich` or `craft-combobox`.

## Requirements

### Semantics

- [x] The control is a native checkbox input whose checked state is the host's `checked`, so the state is exposed without ARIA. Per WCAG 4.1.2 (Name, Role, Value). Verified by `adopts state from a slotted input instead of wiping it` and `remains client-drivable without slotted content` in `checkbox.test.ts`.
- [x] The slotted `<label>` points at the input with `for`, including after a server-rendered input is adopted, so clicking the label toggles the box. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `keeps the label association so label clicks toggle` in `checkbox.test.ts`.
- [ ] The `label` attribute renders a `<label>` associated with the input, the same as the `label` slot. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.
- [x] `disabled`, including `disabled` on a server-rendered input, disables the native input, so it leaves the Tab order and is announced as unavailable. Per WCAG 4.1.2 (Name, Role, Value). Verified by `adopts disabled from the slotted input` in `checkbox.test.ts`.
- [ ] `indeterminate` sets the native input's `indeterminate` property, so it's announced as mixed. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified. The `Indeterminate` story has no `play()` assertion.
- [ ] `label-sr-only` hides the label and help text visually but keeps them as the input's name and description. Focusable content in the help text is revealed while it has focus. Per WCAG 4.1.2 (Name, Role, Value) and 2.4.7 (Focus Visible). Not yet verified. The `NoVisibleLabel` story has no `play()` assertion.

### Forms and validation

- [ ] Help text, from the `help-text` slot, is referenced by the input's `aria-describedby`. Per WCAG 1.3.1 (Info and Relationships) and 3.3.2 (Labels or Instructions). Not yet verified.
- [ ] A `Required` validator sets `aria-required="true"` on the input. Per WCAG 3.3.2 (Labels or Instructions). Not yet verified.
- [ ] While an error is shown, the input carries `aria-invalid="true"`, and messages in the `feedback` slot are referenced by its `aria-describedby`. Per WCAG 3.3.1 (Error Identification) and 4.1.2 (Name, Role, Value). Not yet verified.

### Keyboard and focus

- [ ] The input is in the Tab order, and Space toggles it. Per WCAG 2.1.1 (Keyboard). Not yet verified. `still toggles through Lion after hydration` in `checkbox.test.ts` covers a click, not the keyboard.
- [ ] A keyboard-focused input shows the global focus ring at 3:1 against the surface, in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified.

### Visual

- [ ] The box is the browser's native checkbox (no `appearance: none`), so the checked, unchecked and mixed states are drawn as different shapes, not just colors. Per WCAG 1.4.1 (Use of Color). Not yet verified.
- [ ] The box's border reaches 3:1 against the surface in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified. `choiceInputStyles` sets a border and fill, which browsers largely ignore on a native checkbox.
- [ ] The box, its check mark and the focus ring stay visible in forced colors mode. Not yet verified.
- [ ] The box (`--c-checkbox-size`, 16px) together with its label gives a target of at least 24×24 CSS px, or enough spacing from neighboring targets. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified.
- [ ] Help text (`--c-text-quiet`) reaches 4.5:1 against the surface in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified.

### Reflow and zoom

- [ ] A long label wraps beside the box at a 320px viewport and at 200% zoom, with nothing clipped. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): give every checkbox a visible label that says what checking it does. See [Accessibility](./Checkbox.mdx#accessibility).
- 4.1.2 (Name, Role, Value): use `label-sr-only` only when surrounding content already names the box, such as a table row. See [Accessibility](./Checkbox.mdx#accessibility).
- 4.1.2 (Name, Role, Value): keep `indeterminate` in step with the items it summarizes, or use `craft-checkbox-indeterminate`. See [Accessibility](./Checkbox.mdx#accessibility).
- 3.3.3 (Error Suggestion): write validator messages that say how to fix the choice. See [Accessibility](./Checkbox.mdx#accessibility).
