# `craft-slide-picker-button` accessibility

Follows the WAI-ARIA APG [Disclosure](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/) pattern: a button that pops up a `craft-popover` holding a `craft-slide-picker`. See `popover.a11y.md` for the popover's `aria-expanded`, Escape and focus handling, and `slide-picker.a11y.md` for the slider's keys and value announcements. This file covers only what the button adds.

**Not for:**

- A slide picker with room for segments of at least 24×24 CSS px. `craft-slide-picker` takes one less step there.
- A continuous range, or fine adjustment by dragging. Use `craft-slide-rule`.
- Named choices that aren't points on a scale. Use `craft-select` or `craft-radio-group`.

## Requirements

### Semantics

- [x] The button is named by its tooltip's text, the value as `value-unit` or `valueLabel` formats it, followed by `context`, or by `label` when there's no context. The tooltip is the button's only visible label, so the name starts with it, and the name says the value the segments draw. The segments add nothing to the name. Per WCAG 1.1.1 (Non-text Content), 2.5.3 (Label in Name) and 4.1.2 (Name, Role, Value). Verified by `names the button by the value its tooltip shows, then what it controls: $name` and `names the button with valueLabel` in `slide-picker-button.browser.test.ts`.
- [x] The tooltip names the button and doesn't also describe it, so the value isn't read twice. Per WCAG 4.1.2 (Name, Role, Value). Verified by `shows the start of the button’s name in its tooltip, without also describing the button` in `slide-picker-button.browser.test.ts`.
- [x] The popover's slider is named by the visible `label`, shown below it. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `names the popover’s slider by the label below it` in `slide-picker-button.browser.test.ts`.
- [x] `disabled`, or a disabled `<fieldset>` around the button, disables the button, and clearing it enables the button again. Per WCAG 4.1.2 (Name, Role, Value). Verified by `disables the button when disabled, or inside a disabled fieldset, and enables it again` in `slide-picker-button.browser.test.ts`.
- [x] `readonly` still opens the popover, with a read-only slider, so the value can be reached and read. Per WCAG 4.1.2 (Name, Role, Value). Verified by `keeps the popover’s slider from changing while read-only` in `slide-picker-button.browser.test.ts`.

### Keyboard and focus

- [x] Enter opens the popover, Tab moves to its slider, a key on the slider commits the value and updates the button's name, and Escape closes the popover with focus back on the button. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Verified by `opens with Enter, sets the value from the slider and returns focus on Escape` in `slide-picker-button.browser.test.ts`.
- [x] A keyboard-focused button shows a ring at 3:1 against the surface in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Verified by `shows a focus ring at 3:1 on a keyboard-focused button in the %s theme` in `slide-picker-button.browser.test.ts`.

### Pointer

- [x] The button is at least 24×24 CSS px, and so is each segment of the popover's slider. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `is at least 24×24 CSS px, and so is each segment in the popover` in `slide-picker-button.browser.test.ts`.

### Visual

- [x] The outline around the button's segments, and the outline around its filled run, reach 3:1 against the surface and the empty segments in the light and dark themes. The lines between segments are decoration. Per WCAG 1.4.11 (Non-text Contrast). Verified by `outlines the whole picker, and its filled run, at 3:1 in the %s theme` in `slide-picker-button.browser.test.ts`.
- [x] In forced colors mode, the button's active segments use `Highlight` and inactive ones `Canvas` with a `ButtonBorder` edge, with a `Canvas` line between active segments and a `ButtonBorder` line between inactive ones. Verified by `draws filled segments in Highlight and empty ones in Canvas with a ButtonBorder edge, with lines between them, in forced colors mode` in `slide-picker-button.browser.test.ts`.

### Reflow and zoom

- [x] The popover is as wide as its slider, and a long label wraps to that width rather than widening it. Per WCAG 1.4.10 (Reflow). Verified by `wraps a long label to the slider’s width` in `slide-picker-button.browser.test.ts`.

## Depends on usage

- 2.4.6 (Headings and Labels): set `context` when several buttons share a `label`. See [Accessibility](./SlidePickerButton.mdx#accessibility).
- 1.3.1 (Info and Relationships): set `value-unit` or `valueLabel` when the number alone doesn't say what it counts. See [Accessibility](./SlidePickerButton.mdx#accessibility).
