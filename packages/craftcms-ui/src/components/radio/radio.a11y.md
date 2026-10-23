# `craft-radio` accessibility

Used inside `craft-radio-group`; see `radio-group.a11y.md` for the pattern.

The radio renders a native `<input type="radio">` in the light DOM, with Lion's custom graphic hidden, so the browser draws the control and supplies its role, state and keyboard behavior.

## Requirements

### Semantics

- [x] The label is a `<label>` whose `for` points at the native input, so the input is named by the visible label and clicking the label selects the radio. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `keeps the label association so label clicks select` in `radio.test.ts`.
- [x] A server-rendered input keeps its `checked` state when the component upgrades, so the state announced matches the state shown. Per WCAG 4.1.2 (Name, Role, Value). Verified by `adopts state from a slotted input instead of wiping it` in `radio.test.ts`.
- [ ] `disabled` disables the native input, so it leaves the arrow-key cycle and is announced as unavailable, including when the server-rendered input is disabled. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] Help text, from the `help-text` attribute or slot, is referenced by the native input's `aria-describedby`. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.

### Keyboard and focus

- [ ] A keyboard-focused radio shows a focus ring at 3:1 against the surface, in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified.

### Visual

- [ ] A checked radio shows a filled dot that an unchecked one doesn't, so the state differs by shape and not only by color. Per WCAG 1.4.1 (Use of Color). Not yet verified.
- [ ] The radio's circle border reaches 3:1 against the surface, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] In forced colors mode the circle, the checked dot and the focus ring stay visible. Not yet verified.
- [ ] The radio and its label together form a target at least 24×24 CSS px, or the radio alone has room for a 24px circle that clears its neighbors. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): give every radio a visible label. See [Accessibility](./Radio.mdx#accessibility).
- 1.3.1 (Info and Relationships): put each radio in a `craft-radio-group`. See [Accessibility](./Radio.mdx#accessibility).
