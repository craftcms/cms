# `craft-select-color` accessibility

A `craft-select-rich` whose options are colors. The rich select supplies the invoker button, the listbox, its keyboard behavior, the label wiring and the `disabled` and `readonly` states; see `select-rich.a11y.md`. This file covers only what `craft-select-color` adds: the color options and their swatches.

**Not for:**

- An arbitrary color, such as a hex value. Use `craft-input-color`.
- Choosing a meaning, such as a status. Offer the meanings by name with `craft-select` or `craft-radio-group`.

## Requirements

### Semantics

- [x] Inside `craft-field`, the color button is named by the field label and described by its instructions and errors, and clicking the label focuses the button. Per WCAG 1.3.1 (Info and Relationships), 3.3.2 (Labels or Instructions) and 4.1.2 (Name, Role, Value). Verified by `takes its name and description from a surrounding field` and `focuses its button when the field label is clicked` in `select-color.browser.test.ts`.
- [x] Outside a field, the color button is named and described by the elements the host's `aria-labelledby` and `aria-describedby` reference. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `is named and described by the elements its host references` in `select-color.browser.test.ts`.
- [ ] Each option is named by its color in text ("Red", or the `blank-label` for the transparent option), so the list can be used without seeing the swatches. Per WCAG 1.1.1 (Non-text Content) and 1.4.1 (Use of Color). Not yet verified.
- [ ] Each swatch is `aria-hidden`, in the list and in the copy shown in the invoker, so options aren't announced with extra noise. Per WCAG 1.1.1 (Non-text Content). Not yet verified.
- [ ] The selected color is shown in the invoker by its name as well as its swatch. Per WCAG 1.4.1 (Use of Color). Not yet verified.
- [ ] `required` marks the control as required. Per WCAG 3.3.2 (Labels or Instructions) and 4.1.2 (Name, Role, Value). Known gap: `required` only reflects the attribute; nothing requires a value or announces it.

### Visual

- [ ] The transparent option's checkerboard, and the swatches, keep a visible edge against the surface. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified. Each swatch's edge is its card header's border color (the transparent option's lightens in the dark theme), below 3:1 for pale swatches; the name carries the meaning, so the swatch edge isn't required.
- [ ] In forced colors mode the swatches lose their fill and edge, and every option and the invoker still show the color name. Not yet verified.

### Reflow and zoom

- [ ] An option's name is kept to one line. At a 320px viewport or 200% zoom, long translated names stay readable in the list and the invoker. Per WCAG 1.4.10 (Reflow). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): put the control in a `craft-field`, or set `label` outside one. See [Accessibility](./SelectColor.mdx#accessibility).
- 1.4.1 (Use of Color): when a color carries a meaning, say so in the label or help text. See [Accessibility](./SelectColor.mdx#accessibility).
