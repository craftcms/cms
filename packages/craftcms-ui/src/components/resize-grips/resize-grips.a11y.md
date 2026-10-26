# `craft-resize-grips` accessibility

No WAI-ARIA pattern covers resizing a box in two dimensions. The [window splitter](https://www.w3.org/WAI/ARIA/apg/patterns/windowsplitter/) resizes one pane along one axis and reports a single value, so the corner grip is a plain button instead, named for what it does.

**Not for:**

- Resizing one pane against another, along one axis. Use the window splitter pattern, such as the CP's details sidebar handle.

## Requirements

### Keyboard and focus

- [x] Only the corner grip takes focus. The edge grips are hidden from assistive technology and left out of the tab order, so the box's resizing is reached once rather than three times. Per WCAG 2.4.3 (Focus Order). Verified by `lets only the corner grip take focus, resizing by arrow keys and resetting with Enter` in `resize-grips.browser.test.ts`.
- [x] The corner grip is a button named "Resize" by default. Per WCAG 4.1.2 (Name, Role, Value). Verified by `lets only the corner grip take focus, resizing by arrow keys and resetting with Enter` in `resize-grips.browser.test.ts`.
- [x] Arrow keys resize the box from the corner grip (Shift for a bigger step), and Enter puts it back to its own size, so it can be resized without dragging. Per WCAG 2.1.1 (Keyboard). Verified by `lets only the corner grip take focus, resizing by arrow keys and resetting with Enter` in `resize-grips.browser.test.ts`.
- [ ] The focused corner grip shows a focus ring and its grip line. Per WCAG 2.4.7 (Focus Visible). Not yet verified.
- [ ] The key bindings are discoverable. Per WCAG 3.3.2 (Labels or Instructions). Known gap: the button's name says what it does, but nothing says that arrow keys resize the box.

### Pointer

- [ ] Resizing doesn't depend on dragging. Per WCAG 2.5.7 (Dragging Movements). Known gap: a pointer can only drag a grip. The keyboard covers it from the corner grip, but there's no single-pointer alternative.
- [ ] The corner grip is at least 24 by 24 CSS pixels, or spaced as far from the edge grips. Per WCAG 2.5.8 (Target Size (Minimum)). Known gap: it's 16 by 16, beside the edge grips.

### Reflow and zoom

- [x] A resized box stays within its own maximum size, and is clamped again when a window resize lowers that cap, so it never runs off screen. Per WCAG 1.4.10 (Reflow). Verified by `clamps a resized box again when a window resize lowers its cap` in `resize-grips.browser.test.ts`.

### Visual

- [ ] The grip lines reach 3:1 against the surface and the page behind it, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] In forced-colors mode, a hovered, focused or dragged grip shows its line in `Highlight`. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] With reduced motion preferred, grip lines appear without a fade. Per WCAG 2.3.3 (Animation from Interactions). Not yet verified.

## Depends on usage

- 2.4.6 (Headings and Labels): name the corner grip for what it resizes with `label`. See [Accessibility](./ResizeGrips.mdx#accessibility).
