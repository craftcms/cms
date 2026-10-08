# `craft-tooltip` accessibility

## Requirements

- [x] Keyboard focus on the invoker shows the tooltip, and it stays shown while the invoker keeps keyboard focus, even if the pointer moves elsewhere, per WCAG 1.4.13 (Content on Hover or Focus). Verified by `tooltip.browser.test.ts`'s "stays open while the invoker has keyboard focus".
- [x] Focus from a pointer press doesn't hold the tooltip open: once the pointer leaves an invoker it clicked, the tooltip hides, as it would after a plain hover. Verified by `tooltip.browser.test.ts`'s "hides once the pointer leaves an invoker it was clicked on".
- [x] The tooltip doesn't show while its invoker is disabled. Verified by `overlay-hover.test.ts`'s "does not show while the invoker is disabled".
- [ ] The pointer can move from the invoker onto the tooltip without it hiding (WCAG 1.4.13, hoverable). Not covered by a test yet.
- [ ] <kbd>Escape</kbd> dismisses the tooltip without moving focus (WCAG 1.4.13, dismissible). Not covered by a test yet.
