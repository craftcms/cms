# `craft-select-color` accessibility

Built on `craft-select-rich`, which supplies the invoker button, the listbox, its keyboard behavior and the label wiring. This file covers only what `craft-select-color` adds: the color options and their swatches.

**Not for:**

- An arbitrary color, such as a hex value. Use `craft-input-color`.
- Choosing a meaning, such as a status. Offer the meanings by name with `craft-select` or `craft-radio-group`.

## Requirements

### Semantics

- [x] `label` and `name` are forwarded to the inner `craft-select-rich`, which renders the label. Per WCAG 3.3.2 (Labels or Instructions) and 4.1.2 (Name, Role, Value). Verified by the `Default` story.
- [ ] Each option is named by its color in text ("Red", or the `blank-label` for the transparent option), so the list can be used without seeing the swatches. Per WCAG 1.1.1 (Non-text Content) and 1.4.1 (Use of Color). Not yet verified.
- [ ] Each swatch is `aria-hidden`, in the list and in the copy shown in the invoker, so options aren't announced with extra noise. Per WCAG 1.1.1 (Non-text Content). Not yet verified.
- [ ] The selected color is shown in the invoker by its name as well as its swatch. Per WCAG 1.4.1 (Use of Color). Not yet verified.
- [ ] `disabled`, `readonly` and `required` are forwarded to the inner `craft-select-rich`. Per WCAG 4.1.2 (Name, Role, Value) and 3.3.2 (Labels or Instructions). Not yet verified.

### Visual

- [ ] The transparent option's checkerboard, and the swatches, keep a visible edge against the surface. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified. The edge is a `rgb(0 0 0 / 15%)` inset shadow, below 3:1 for pale swatches; the name carries the meaning, so the swatch edge isn't required.
- [ ] In forced colors mode the swatches lose their fill and edge, and every option and the invoker still show the color name. Not yet verified.

### Reflow and zoom

- [ ] An option's name is kept to one line. At a 320px viewport or 200% zoom, long translated names stay readable in the list and the invoker. Per WCAG 1.4.10 (Reflow). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): set `label`. See [Accessibility](./SelectColor.mdx#accessibility).
- 1.4.1 (Use of Color): when a color carries a meaning, say so in the label or help text. See [Accessibility](./SelectColor.mdx#accessibility).
