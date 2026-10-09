# `craft-truncate` accessibility

## Requirements

### Semantics

- [ ] The full content stays in the DOM and is only clipped visually (`overflow: hidden`), so assistive technology reads the whole value, not the clipped one. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.

### Full text

- [x] The full text appears in a tooltip only when the content actually overflows, and no tooltip renders when it fits. Per WCAG 1.4.10 (Reflow). Verified by `renders a tooltip with the full text when the content overflows` and `does not render a tooltip when the content fits` in `truncate.test.ts`, with the overflow simulated.
- [ ] Keyboard users can reveal the full text. Per WCAG 2.1.1 (Keyboard). Known gap: the tooltip's invoker is the inner `<span>`, which can't take focus. Focus reaches it only when the slotted content holds a focusable element, such as a link, so clipped plain text can't be read from the keyboard.
- [ ] Touch users can reveal the full text. Per WCAG 1.4.10 (Reflow). Known gap: the tooltip opens on hover and keyboard focus only, and neither happens on a tap on plain text.

### Visual

- [x] A slotted link keeps its own underline unless an ancestor sets `--c-truncate-link-decoration`, so wrapping a link in `craft-truncate` doesn't strip the cue that marks it as a link. Per WCAG 1.4.1 (Use of Color). Verified by `leaves a link in a truncate outside a chip as it is` in `chip.browser.test.ts`.

## Depends on usage

- 1.4.10 (Reflow): truncate only text whose full value someone can reach another way, and set `disabled` only where the full value shows elsewhere. See [Accessibility](./Truncate.mdx#accessibility).
- 2.1.1 (Keyboard): for clipped plain text that matters, make the content a link or show the full value elsewhere. See [Accessibility](./Truncate.mdx#accessibility).
