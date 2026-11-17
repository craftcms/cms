# `craft-slide-picker` accessibility

Follows the WAI-ARIA APG [Slider](https://www.w3.org/WAI/ARIA/apg/patterns/slider/) pattern, for a short run of fixed steps.

**Not for:**

- A continuous range, or fine adjustment by dragging. Use `craft-slide-rule`.
- An exact number someone already knows, or a wide range. Use `craft-input` with `type="number"`.
- Named choices that aren't points on a scale. Use `craft-radio-group` or `craft-select`.
- A picker without room for segments of at least 24×24 CSS px, which is most of them at the default size. Use `craft-slide-picker-button`, the preferred way to pick from a short scale.

## Requirements

### Semantics

- [x] The slider is a `role="slider"` element carrying `aria-valuemin`, `aria-valuemax` and `aria-valuenow`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `exposes slider ARIA state` in `slide-picker.test.ts`.
- [x] `aria-valuetext` announces the value with `value-unit` appended, or as `valueLabel` formats it, so a bare number isn't all a screen reader gets. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `announces value-unit %j as %j` and `announces the value as valueLabel formats it` in `slide-picker.test.ts`.
- [x] A `value-unit` word is separated from the value by a space, so `value-unit="columns"` announces "3 columns", while a symbol such as `%` attaches to it ("100%"). Per WCAG 1.3.1 (Info and Relationships). Verified by `announces value-unit %j as %j` in `slide-picker.test.ts`.
- [x] The slider is named by the picker's `label`, by a surrounding `craft-field` label, or by the elements the picker's `labelled-by` references. Per WCAG 1.3.1 (Info and Relationships), 2.5.3 (Label in Name) and 4.1.2 (Name, Role, Value). Verified by `takes its name and description from a surrounding field`, `is named by its label, and focused when the label is clicked`, `is named and described by the elements its host references` and `follows changes to the references on its host` in `slide-picker.browser.test.ts`.
- [x] A picker with no label isn't given a name that may be wrong. Per WCAG 2.4.6 (Headings and Labels). Verified by `has no name of its own until one is given` in `slide-picker.browser.test.ts`.
- [x] The slider is described by its help text and errors, a surrounding `craft-field`'s instructions and errors, or the elements the picker's `described-by` references. Per WCAG 1.3.1 (Info and Relationships) and 3.3.1 (Error Identification). Verified by `takes its name and description from a surrounding field` and `is named and described by the elements its host references` in `slide-picker.browser.test.ts`.
- [x] `disabled`, or a disabled `<fieldset>` around the picker, sets `aria-disabled="true"` and removes the slider from the Tab order, and re-enabling the fieldset brings it back unless `disabled` is set. Per WCAG 4.1.2 (Name, Role, Value). Verified by `ignores input while disabled` in `slide-picker.test.ts` and `comes back from a disabled fieldset, and stays disabled if it was set so` in `slide-picker.browser.test.ts`.
- [x] `readonly` sets `aria-readonly="true"` and ignores pointer and keyboard input. Per WCAG 4.1.2 (Name, Role, Value). Verified by `ignores input while read-only, but stays in the Tab order` in `slide-picker.test.ts`.
- [x] A read-only slider stays in the Tab order, so its value can still be reached and read. Per WCAG 2.1.1 (Keyboard). Verified by `ignores input while read-only, but stays in the Tab order` in `slide-picker.test.ts`.

### Keyboard and focus

- [x] The slider is one Tab stop. Up Arrow raises the value by one step, and Home and End set the minimum and maximum. Each key press commits the value. Per WCAG 2.1.1 (Keyboard). Verified by `moves the value with the keyboard and emits each change` in `slide-picker.test.ts`.
- [x] Tab lands on the slider itself, and clicking the label focuses it. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Verified by `is one Tab stop, on the slider` and `is named by its label, and focused when the label is clicked` in `slide-picker.browser.test.ts`.
- [x] Down Arrow lowers the value by one step, and Right and Left Arrow raise and lower it, reversed in a right-to-left context. Per WCAG 2.1.1 (Keyboard). Verified by `moves from 50 to $value with $key in a $dir context` in `slide-picker.test.ts`.
- [ ] Page Up and Page Down aren't supported. The APG lists them as optional, so this only matters for a long range. Per WCAG 2.1.1 (Keyboard).
- [x] A keyboard-focused slider shows the focus ring around the segment for the current value, offset onto the surface, at 3:1 against the surface in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Verified by `draws one focus ring, around the current segment, at 3:1 in the %s theme` in `slide-picker.browser.test.ts`.

### Pointer

- [x] Clicking a segment sets the value with a single pointer action. There's no dragging. Per WCAG 2.5.7 (Dragging Movements). Verified by `emits input then change on segment click` in `slide-picker.test.ts`.
- [ ] Every segment is at least 24×24 CSS px, or has room around it. Per WCAG 2.5.8 (Target Size (Minimum)). Known gap at the default size: segments are 0.5rem wide and touch each other. The keyboard isn't a pointer alternative for this criterion.

### Visual

- [x] The selected range is marked by a filled run with its own outline, and a previewed range by a 2px outline, so neither relies on hue alone. Per WCAG 1.4.1 (Use of Color). Verified by `edges empty segments, and fills, outlines and divides selected ones, at 3:1 in the %s theme` and `outlines the previewed run 2px thick at 3:1, unlike the selected run, in the %s theme` in `slide-picker.browser.test.ts`.
- [x] Empty segments' edges, the selected fill and its outline, and the dividers between selected segments reach 3:1 against what's beside them in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Verified by `edges empty segments, and fills, outlines and divides selected ones, at 3:1 in the %s theme` in `slide-picker.browser.test.ts`.
- [x] A previewed run's outline reaches 3:1 against the surface and its fill in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Verified by `outlines the previewed run 2px thick at 3:1, unlike the selected run, in the %s theme` in `slide-picker.browser.test.ts`.
- [x] A read-only picker is drawn at full strength, so it keeps the same contrast. Per WCAG 1.4.11 (Non-text Contrast). Verified by `draws read-only segments at full strength` in `slide-picker.browser.test.ts`.
- [x] In forced colors mode, a previewed run has a 2px `Highlight` edge. Verified by `outlines the previewed run 2px thick in forced colors mode` in `slide-picker.browser.test.ts`.
- [x] In forced colors mode, active segments use `Highlight` and inactive ones `Canvas` with a `ButtonBorder` edge, a `Canvas` line divides active segments inside their outline, and the focus ring stays visible. Verified by `tells selected and empty segments apart, divides selected ones, and shows the focus ring, in forced colors mode` in `slide-picker.browser.test.ts`.

### Reflow and zoom

- [x] Segments are sized in `rem`, so the control scales with text size and fits a 320px viewport for the step counts it's meant for. Per WCAG 1.4.4 (Resize Text) and 1.4.10 (Reflow). Verified by `scales its segments with the text size` and `fits a 320px-wide viewport without scrolling sideways` in `slide-picker.browser.test.ts`.

## Depends on usage

- 3.3.2 (Labels or Instructions): give the picker a visible label. See [Accessibility](./SlidePicker.mdx#accessibility).
- 1.3.1 (Info and Relationships): set `value-unit` or `valueLabel` when the number alone doesn't say what it counts. See [Accessibility](./SlidePicker.mdx#accessibility).
- 2.5.8 (Target Size (Minimum)): set `--c-slide-picker-segment-width` and `--c-slide-picker-segment-height` to at least `1.5rem`, or use `craft-slide-picker-button`. See [Accessibility](./SlidePicker.mdx#accessibility).
