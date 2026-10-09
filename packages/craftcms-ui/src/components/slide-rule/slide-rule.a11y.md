# `craft-slide-rule` accessibility

Follows the WAI-ARIA APG [Slider](https://www.w3.org/WAI/ARIA/apg/patterns/slider/) pattern, drawn as a ruler that slides under a fixed cursor.

**Not for:**

- A short set of discrete steps. Use `craft-slide-picker`.
- Values that aren't angles. The announced value always ends in "degrees".
- The only way to enter an exact number. Pair it with `craft-input` with `type="number"`.

## Requirements

### Semantics

- [x] The control is a `role="slider"` element carrying `aria-valuemin`, `aria-valuemax`, `aria-valuenow`, and an `aria-valuetext` that includes the value. Per WCAG 4.1.2 (Name, Role, Value). Verified by `exposes slider ARIA state` in `slide-rule.test.ts`.
- [ ] `aria-valuetext` reads as a whole number of degrees ("12 degrees"), while `aria-valuenow` keeps any fraction. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.
- [ ] `label` is the slider's accessible name, applied as `aria-label`. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] The accessible name comes from a visible label. Per WCAG 2.5.3 (Label in Name) and 3.3.2 (Labels or Instructions). Known gap: `label` only sets `aria-label` and defaults to "Rotate". A `<label for>` or `craft-field` label names the host, which doesn't reach the slider in the shadow root.
- [x] `disabled` sets `aria-disabled="true"`, removes the slider from the Tab order and ignores the keyboard. Per WCAG 4.1.2 (Name, Role, Value). Verified by `ignores the keyboard while disabled` in `slide-rule.test.ts`.
- [ ] The graduation marks and their numbers are hidden from assistive technology, leaving the slider's own value as the only thing announced. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.

### Keyboard and focus

- [x] The slider is one Tab stop. Right Arrow raises the value by 1, Page Up by 10, and Home and End set the minimum and maximum. Each key press commits the value. Per WCAG 2.1.1 (Keyboard). Verified by `moves the value with the keyboard, emitting input and change per step` in `slide-rule.test.ts`.
- [x] Down Arrow lowers the value by 1, and a key that can't move the value past the range is clamped and emits nothing. Per WCAG 2.1.1 (Keyboard). Verified by `stays quiet when a move lands on the value already showing` and `clamps to the slide range even when pushed past it` in `slide-rule.test.ts`.
- [ ] Left Arrow and Page Down lower the value by 1 and 10. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] Right and Left Arrow are reversed in a right-to-left context. Per WCAG 2.1.1 (Keyboard). Known gap: Right Arrow always raises the value.
- [ ] A keyboard-focused slider shows the focus ring around the cursor, at 3:1 against the surface in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified.

### Pointer

- [ ] A tap on the ruler sets the value at that point, so dragging is never required. Per WCAG 2.5.7 (Dragging Movements). Not yet verified.
- [ ] The ruler, which takes the press, is at least 24 CSS px tall. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified.

### Visual

- [x] The value is shown by the extent of a band running from the zero mark to the cursor, not by its color, and the band lands on fractional values between marks. Per WCAG 1.4.1 (Use of Color). Verified by the `ValueIndicator` story.
- [ ] The cursor, the marks and the band's borders reach 3:1 against the surface in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified. The marks take `currentColor` unless themed.
- [ ] The mark numbers reach 4.5:1 where the value is read. Per WCAG 1.4.3 (Contrast (Minimum)). Known gap: the numbers are 8px, and the edge mask fades them to transparent toward both ends.
- [ ] In forced colors mode, the cursor, marks, band and focus ring stay visible. Not yet verified. The stylesheet has no `forced-colors` rules.
- [ ] The strip's 200ms slide stops under `prefers-reduced-motion`. Per WCAG 2.3.3 (Animation from Interactions), a AAA criterion. Known gap: the transitions run regardless.

### Reflow and zoom

- [ ] The ruler fills its container's width and keeps working at a 320px viewport. Per WCAG 1.4.10 (Reflow). Not yet verified.
- [ ] The mark numbers scale with text size. Per WCAG 1.4.4 (Resize Text). Known gap: their size is set in `px`.

## Depends on usage

- 3.3.2 (Labels or Instructions): show a visible label beside the rule and pass the same text as `label`. See [Accessibility](./SlideRule.mdx#accessibility).
