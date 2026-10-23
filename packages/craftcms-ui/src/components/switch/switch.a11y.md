# `craft-switch` accessibility

Follows the WAI-ARIA APG [Switch](https://www.w3.org/WAI/ARIA/apg/patterns/switch/) pattern.

Shares `HasLabel` with `craft-input`; see `input.a11y.md` for label, help text and error requirements. The control itself is `craft-switch-button`; see `switch-button.a11y.md` for its role, state and visuals.

**Not for:**

- A choice that's submitted with a form rather than applied straight away. Use `craft-checkbox`.
- Picking one of several options. Use `craft-radio-group`.
- A button that turns a tool or view on and off. Use `craft-button` with `toggle`, which sets `aria-pressed`.

## Requirements

### Semantics

- [ ] The `label` attribute or slotted `label` is added to the switch button's `aria-labelledby`, so its accessible name is the visible label. Per WCAG 4.1.2 (Name, Role, Value) and 2.5.3 (Label in Name). Not yet verified.
- [x] Server-rendered `aria-labelledby` and `aria-describedby` references on a slotted switch button are kept when Lion rebuilds those attributes. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `keeps server-rendered aria-labelledby and aria-describedby references` in `switch.test.ts`.
- [x] The visible `on-label` and `off-label` text is hidden from assistive technology, so it isn't read as two loose words beside the control. Per WCAG 1.3.1 (Info and Relationships). Verified by `hides the visible state labels from assistive technology` in `switch.test.ts`.
- [x] When `on-label` or `off-label` is set, a visually hidden description of the states is linked to the switch button with `aria-describedby`, and unlinked when both are cleared. Per WCAG 1.3.1 (Info and Relationships). Verified by `links the state description to the switch via aria-describedby`, `updates the labels and description when properties change` and `unlinks the description when the labels are removed` in `switch.test.ts`.
- [ ] The state description describes on and off. Per WCAG 1.3.1 (Info and Relationships). Known gap: it reads "Check for … Uncheck for …", checkbox wording, while a screen reader announces the control as a switch that's on or off.
- [ ] The indeterminate state is exposed to assistive technology. Per WCAG 4.1.2 (Name, Role, Value). Known gap: the switch button sets `aria-checked="mixed"`, which `role="switch"` doesn't support, so it's announced as off.
- [ ] `disabled` is passed to the switch button, so it leaves the Tab order and is announced as unavailable. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.

### Keyboard and focus

- [ ] Space and Enter toggle the switch. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [x] Right Arrow turns the switch on and Left Arrow turns it off, swapped in right-to-left text. Left Arrow also clears the indeterminate state. Per WCAG 2.1.1 (Keyboard). Verified by `turns on with ArrowRight and off with ArrowLeft` and `clears the indeterminate state with ArrowLeft without toggling` in `switch.test.ts`. No test covers right-to-left.
- [x] Toggling takes effect at once, with no separate submit step: the state changes and a `change` event fires. Per WCAG 3.2.2 (On Input), since nothing beyond the setting itself changes. Verified by `still toggles checked state with labels present` and `fires a single change event on unmuted turnOn()/turnOff()` in `switch.test.ts`.

### Pointer

- [x] Clicking a state label sets that state, and does nothing while disabled. Per WCAG 2.5.8 (Target Size (Minimum)), since the label adds to the area that sets the state. Verified by `sets the state when a state label is clicked` and `ignores state label clicks when disabled` in `switch.test.ts`.
- [x] Clicking an external `<label>` linked to the switch button toggles it. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `toggles when an external label element is clicked` in `switch.test.ts`.

### Reflow and zoom

- [x] The switch row is only as tall as the switch button, at both sizes. Per WCAG 1.4.10 (Reflow). Verified by `is only as tall as its switch` in `switch.browser.test.ts`.
- [ ] Long `on-label` and `off-label` text wraps at a 320px viewport instead of pushing the switch past its container. Per WCAG 1.4.10 (Reflow). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): give every switch a visible label that names the setting, not its states. See [Accessibility](./Switch.mdx#accessibility).
- 2.5.3 (Label in Name): an external `aria-labelledby` label must contain the text shown beside the switch. See [Accessibility](./Switch.mdx#accessibility).
- 3.2.2 (On Input): use a switch only for a setting that applies on toggle, and don't navigate or submit a form when it's toggled. See [Accessibility](./Switch.mdx#accessibility).
- 2.5.8 (Target Size (Minimum)): keep 24px around a `size="small"` switch clear of other targets, or give it a clickable label. See [Accessibility](./Switch.mdx#accessibility).
