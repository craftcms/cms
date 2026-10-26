# `craft-spinner` accessibility

**Not for** (use another component instead):

- Work with a known percentage or item count (→ `craft-progress-bar`, or `craft-progress` in a small space).

## Requirements

### Semantics

- [x] Slotted text is visually hidden but kept in the accessibility tree as the spinner's text alternative. Per WCAG 1.1.1 (Non-text Content). Verified by `keeps its slotted label for assistive technology` in `spinner.test.ts`.
- [x] The `label`, when nothing is slotted, is visually hidden but kept in the accessibility tree as the spinner's text alternative. Per WCAG 1.1.1 (Non-text Content). Verified by `announces its label when nothing is slotted` in `spinner.test.ts`.
- [x] A hidden spinner drops its slotted text from the accessibility tree along with the animation. Per WCAG 1.3.1 (Info and Relationships). Verified by `hides without leaving the layout` in `spinner.test.ts`, which asserts the `hidden` class. That class hides the spinner with display: none.
- [ ] A spinner with no `label` or slotted text still has a text alternative. Per WCAG 1.1.1 (Non-text Content). Known gap: there is no default text such as "Loading", so an empty spinner is silent.
- [ ] Showing the spinner is announced as a status message. Per WCAG 4.1.3 (Status Messages). Known gap: there is no `role="status"` or live region, so the slotted text is only read when a screen reader reaches it. `show()` doesn't announce, although its JSDoc says it does.

### Color and contrast

- [ ] The arc (`currentcolor` at 80% opacity) reaches 3:1 against the surface. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] In forced-colors mode, the arc stays visible and still reads as a partial ring. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified. The ring is two colored and two transparent borders, which forced colors may recolor.

### Motion

- [ ] With `prefers-reduced-motion: reduce`, the spinner stops rotating. Per WCAG 2.3.3 (Animation from Interactions). Known gap: the stylesheet has no reduced-motion rule.
- [ ] The rotation, which loops for as long as the spinner shows, can be stopped. Per WCAG 2.2.2 (Pause, Stop, Hide). Known gap: nothing stops it but hiding the spinner.

## Depends on usage

- 1.1.1 (Non-text Content): set a `label`, or slot text, saying what is loading, unless nearby text already says it. See [Accessibility](./Spinner.mdx#accessibility).
