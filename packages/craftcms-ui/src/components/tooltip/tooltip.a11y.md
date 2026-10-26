# `craft-tooltip` accessibility

Follows the WAI-ARIA APG [Tooltip](https://www.w3.org/WAI/ARIA/apg/patterns/tooltip/) pattern.

**Not for** (use another component instead):

- Content with links, buttons, form controls or anything else focusable. A tooltip's content can't take focus (→ `craft-popover`).
- Information someone needs to complete a task, which a touch user can't hover to find (→ visible help text, such as `craft-field`'s instructions, or `craft-info-icon`).
- Extra detail opened by pressing a button and kept open until dismissed (→ `craft-info-icon`, which drives `craft-tooltip` with `trigger="click"`).
- A label or description on an element that can't take keyboard focus. Keyboard users never see it.

A change that needs any of these is a different pattern. Don't add a mode to `craft-tooltip` for it.

## Requirements

### Semantics

- [ ] The content carries `role="tooltip"`, and the invoker references it with `aria-describedby` (or `aria-labelledby` when `invoker-relation="label"`). Per WCAG 4.1.2 (Name, Role, Value). Lion's `OverlayController` sets both; not yet verified by a test.
- [ ] The wiring follows a `for` change to a new invoker, and is removed from the old one. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.

### Keyboard and pointer

- [x] Keyboard focus on the invoker shows the tooltip. Per WCAG 2.1.1 (Keyboard). Verified for an external `for` invoker by `stays open while the invoker has keyboard focus` in `tooltip.browser.test.ts`, and for a slotted invoker by `exposes translation help on keyboard focus and dismisses it with Escape` in `field.browser.test.ts`.
- [x] The tooltip stays shown while the invoker keeps keyboard focus, even when the pointer moves elsewhere. Per WCAG 1.4.13 (Content on Hover or Focus), persistent. Verified by `stays open while the invoker has keyboard focus` in `tooltip.browser.test.ts`.
- [x] Focus from a pointer press doesn't hold the tooltip open: once the pointer leaves an invoker it clicked, the tooltip hides, as it would after a plain hover. Verified by `hides once the pointer leaves an invoker it was clicked on` in `tooltip.browser.test.ts`.
- [x] Escape dismisses the tooltip while the invoker has focus, and focus stays on the invoker. Per WCAG 1.4.13 (Content on Hover or Focus), dismissible. Verified by `exposes translation help on keyboard focus and dismisses it with Escape` in `field.browser.test.ts`.
- [ ] Escape dismisses a tooltip shown by hover alone, while focus is elsewhere. Per WCAG 1.4.13 (Content on Hover or Focus), dismissible. Known gap: Lion listens for Escape only on the invoker and the content, and `craft-tooltip` doesn't set `hidesOnOutsideEsc`, so a hovered tooltip can't be dismissed without moving the pointer.
- [ ] The pointer can move from the invoker onto the tooltip without it hiding. Per WCAG 1.4.13 (Content on Hover or Focus), hoverable. Not yet verified, and likely a known gap: the hide delay is `0` and the tooltip sits 4px from the invoker, so the hide fires before the pointer can cross the gap.
- [x] The tooltip doesn't show while its invoker is disabled, unless the invoker stays focusable while disabled, so the tooltip can say why. Verified by `does not show while the invoker is disabled` and `shows while a disabled invoker stays focusable` in `overlay-hover.test.ts`.
- [ ] The tooltip doesn't take focus or move it when it shows or hides. Per WCAG 3.2.1 (On Focus). Lion's tooltip config sets no `elementToFocusAfterHide`; not yet verified by a test.

### Visual

- [ ] The content text reaches 4.5:1 against its background, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified.
- [ ] The tooltip's edge reaches 3:1 against the page surface it sits on, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] The tooltip keeps a visible edge in forced colors mode. Its 1px border should be forced to a system color while the shadow drops out. Not yet verified.
- [x] The tooltip shows in full beside its invoker, lifted into the top layer, so an ancestor that clips, scrolls or forms a containing block, such as a blurred sticky footer, doesn't cut it off. Per WCAG 1.4.10 (Reflow). Verified by `shows in full beside an invoker in a blurred backdrop that clips` in `tooltip.browser.test.ts`.
- [ ] The tooltip stays within the viewport at 320 CSS px wide and 400% zoom. Per WCAG 1.4.10 (Reflow). Not yet verified by a test. Long content wraps at `--c-tooltip-max-width`, which defaults to `min(45ch, 100vw - 2rem)`.
- [ ] The tooltip doesn't cover the focused invoker. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)). Popper places it beside the invoker; not yet verified when it flips at a viewport edge.

### Motion

- [ ] The tooltip appears and disappears without animation, after a 200ms show delay, so there's nothing for `prefers-reduced-motion` to reduce. Not yet verified.

## Depends on usage

- 4.1.2 (Name, Role, Value): give the invoker an accessible name of its own, since the tooltip only describes it by default. See [Accessibility](./Tooltip.mdx#accessibility).
