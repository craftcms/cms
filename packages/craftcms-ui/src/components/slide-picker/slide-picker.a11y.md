# `craft-slide-picker` accessibility

Follows the WAI-ARIA APG [Slider](https://www.w3.org/WAI/ARIA/apg/patterns/slider/) pattern, for a short run of fixed steps.

**Not for:**

- A continuous range, or fine adjustment by dragging. Use `craft-slide-rule`.
- An exact number someone already knows, or a wide range. Use `craft-input` with `type="number"`.
- Named choices that aren't points on a scale. Use `craft-radio-group` or `craft-select`.

## Requirements

### Semantics

- [x] The control is a `role="slider"` element carrying `aria-valuemin`, `aria-valuemax` and `aria-valuenow`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `exposes slider ARIA state` in `slide-picker.test.ts`.
- [x] `aria-valuetext` announces the value with `value-unit` appended, or as `valueLabel` formats it, so a bare number isn't all a screen reader gets. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `formats the value text with valueUnit and valueLabel` in `slide-picker.test.ts`.
- [ ] `value-unit` is separated from the value by a space, so `value-unit="columns"` announces "3 columns". Per WCAG 1.3.1 (Info and Relationships). Known gap: the unit is appended with no space, so it announces "3columns".
- [ ] The slider takes its accessible name from its field label, whether that's a `craft-field` label or a `<label for>` pointing at the picker. Per WCAG 1.3.1 (Info and Relationships), 2.5.3 (Label in Name) and 4.1.2 (Name, Role, Value). Known gap: a field label names the host, which doesn't reach the slider in the shadow root. The only name is `label`, applied as `aria-label`, and the component draws no label of its own.
- [ ] A picker with no label isn't given a name that may be wrong. Per WCAG 2.4.6 (Headings and Labels). Known gap: `label` defaults to "Number of columns".
- [ ] The slider takes its description from its field's help text, or from the element named by `described-by`. Per WCAG 1.3.1 (Info and Relationships). Known gap: the slider is in the shadow root, so an `aria-describedby` id from the page doesn't resolve, and `craft-field` doesn't set `described-by`.
- [x] `disabled` sets `aria-disabled="true"` and removes the slider from the Tab order. Per WCAG 4.1.2 (Name, Role, Value). Verified by `ignores input while disabled` in `slide-picker.test.ts`.
- [x] `readonly` sets `aria-readonly="true"` and ignores pointer and keyboard input. Per WCAG 4.1.2 (Name, Role, Value). Verified by `ignores input while read-only` in `slide-picker.test.ts`.
- [ ] A read-only slider stays in the Tab order, so its value can still be reached and read. Per WCAG 2.1.1 (Keyboard). Known gap: `readonly` sets `tabindex="-1"`, which `ignores input while read-only` asserts.

### Keyboard and focus

- [x] The slider is one Tab stop. Up Arrow raises the value by one step, and Home and End set the minimum and maximum. Each key press commits the value. Per WCAG 2.1.1 (Keyboard). Verified by `moves the value with the keyboard and emits each change` in `slide-picker.test.ts`.
- [ ] Down Arrow lowers the value by one step, and Right and Left Arrow raise and lower it, reversed in a right-to-left context. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] Page Up and Page Down aren't supported. The APG lists them as optional, so this only matters for a long range. Per WCAG 2.1.1 (Keyboard).
- [ ] A keyboard-focused slider shows the focus ring around the segment for the current value, at 3:1 against the surface in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified.

### Pointer

- [x] Clicking a segment sets the value with a single pointer action. There's no dragging. Per WCAG 2.5.7 (Dragging Movements). Verified by `emits input then change on segment click` in `slide-picker.test.ts`.
- [ ] Every segment is at least 24×24 CSS px, or has room around it. Per WCAG 2.5.8 (Target Size (Minimum)). Known gap: segments are 0.5rem wide and touch each other. The keyboard isn't a pointer alternative for this criterion.

### Visual

- [ ] The selected range is marked by taller segments as well as a darker fill. Per WCAG 1.4.1 (Use of Color). Not yet verified. `marks segments active up to (and including) the current value` checks only the classes.
- [ ] Segment borders, and the active fill against the inactive fill, reach 3:1 against the surface in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] A read-only picker stays at 3:1 after its `opacity: 0.7`. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] In forced colors mode, active segments use `Highlight` and inactive ones `Canvas` with a `ButtonBorder` edge, and the focus ring stays visible. Not yet verified.

### Reflow and zoom

- [ ] Segments are sized in `rem`, so the control scales with text size and fits a 320px viewport for the step counts it's meant for. Per WCAG 1.4.4 (Resize Text) and 1.4.10 (Reflow). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): give the picker a visible label. See [Accessibility](./SlidePicker.mdx#accessibility).
- 1.3.1 (Info and Relationships): set `value-unit` or `valueLabel` when the number alone doesn't say what it counts. See [Accessibility](./SlidePicker.mdx#accessibility).
