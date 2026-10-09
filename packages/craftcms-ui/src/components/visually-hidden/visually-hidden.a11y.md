# `craft-visually-hidden` accessibility

## Requirements

### Semantics

- [x] The content stays in the DOM, slotted, and the host never takes `hidden`, so the content stays available to assistive technology. Per WCAG 1.3.1 (Info and Relationships). Verified by `keeps its content in the DOM` in `visually-hidden.test.ts`.
- [ ] The host hides its content with the clip pattern (`position: absolute`, a 1px box, `clip-path: inset(50%)`), never `display: none` or `visibility: hidden`, so the content stays in the accessibility tree. Per WCAG 1.3.1 (Info and Relationships). Not yet verified in a browser.

### Focus

- [ ] Focusable content inside becomes visible when it takes focus. Per WCAG 2.4.7 (Focus Visible). Known gap: the host stays clipped while its content has focus, so a focused link or button inside is invisible.

### Visual

- [ ] The 1px absolutely positioned box adds no horizontal scrolling at 320 CSS px wide. Per WCAG 1.4.10 (Reflow). Not yet verified.

## Depends on usage

- 2.4.7 (Focus Visible): keep focusable content out of it. See [Accessibility](./VisuallyHidden.mdx#accessibility).
