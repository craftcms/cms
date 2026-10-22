# `craft-popover` accessibility

Follows the WAI-ARIA APG [Disclosure](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/) pattern. It's a generic, non-blocking container that pops up when a button is clicked. What goes inside varies: links, settings, a form, a notification list.

Its open and close behavior comes from APG's [Disclosure Navigation Menu](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/examples/disclosure-navigation/) example:
- **Button:** carries `aria-expanded`.
- **Panel:** not a menu or a dialog. Tab moves through its contents in order.
- **Closing:** Escape closes the panel and returns focus to the button, and focus leaving the panel closes it too.

`craft-action-menu` is built on `craft-popover` and follows that example more directly, adding a list of items with arrow-key navigation.

**Not for:**

- Content that must block the page until it's dismissed, or that traps focus. Use `craft-dialog`.
- A short text hint shown on hover or focus. Use `craft-tooltip`.
- A list of actions, commands or links that should be navigable with arrow keys. Use `craft-action-menu`.
- Choosing a value for a form field. Use `craft-select`, `craft-select-rich` or `craft-combobox`.
- A region that expands in place, in the document flow. Use `craft-disclosure`.

## Requirements

### Semantics

- [x] The invoker carries `aria-expanded`, `false` while the popover is closed and `true` once it opens. Per WCAG 4.1.2 (Name, Role, Value). Verified by `marks the invoker as the thing that expands the overlay` in `popover.test.ts`. That case doesn't assert that `aria-expanded` returns to `false` on close.
- [x] With `without-invoker-aria`, the popover writes no `aria-expanded` onto its invoker, so a positioning anchor that isn't the control isn't given state it can't carry. Per WCAG 4.1.2 (Name, Role, Value). Verified by `leaves the invoker alone when the consumer owns the aria` in `popover.test.ts`.
- [ ] The panel isn't exposed as a dialog or a menu. It's a plain container whose links and controls keep their own roles, as in the Disclosure Navigation Menu example, which avoids `menu` because that role promises keyboard behavior the panel doesn't have. Per WCAG 4.1.2 (Name, Role, Value) and 1.3.1 (Info and Relationships). Known deviation: Lion's `withDropdownConfig()` gives the panel `role="dialog"`, and `label` is applied to it as `aria-label`. Without `label`, the panel is announced as an unnamed dialog.
- [ ] The invoker points at the panel with `aria-controls`, as the Disclosure Navigation Menu example does. Known deviation: the docs for Lion's overlay say it sets `aria-controls` on the invoker, but the installed version doesn't. The panel also lives in the popover's shadow root, so an IDREF from a light-DOM invoker couldn't resolve. `aria-controls` is optional in the Disclosure pattern, so this isn't a conformance failure.

### Keyboard and focus

- [x] Activating the invoker toggles the popover: the first click opens it, the second closes it. Per WCAG 2.1.1 (Keyboard). Verified by `toggles on invoker click` in `popover.test.ts`, which uses `button.click()`. No test presses Enter or Space on the invoker.
- [ ] Escape closes an open popover while focus is on the invoker or inside the panel, and focus moves to the invoker. Per WCAG 2.1.1 (Keyboard), 2.4.3 (Focus Order) and 1.4.13 (Content on Hover or Focus). Not yet verified.
- [x] Escape that closes the popover doesn't also act on what contains it, such as a details panel that closes on Escape, so the user stays where they were. A closed popover leaves Escape to its container. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Verified by `keeps Escape from reaching a container while it closes the popover` in `popover.browser.test.ts`. An invoker linked with `for` sits outside the popover, so Escape pressed on it isn't covered.
- [ ] Moving focus out of the invoker and panel closes the popover, as in the Disclosure Navigation Menu example. Per WCAG 2.4.3 (Focus Order) and 2.4.11 (Focus Not Obscured (Minimum)). Known gap: Lion only dismisses on Escape or an outside click, not on focus out, so the panel stays open behind the next focused control.
- [ ] When `hide()` closes the popover while focus is inside it, focus returns to the invoker. Per WCAG 2.4.3 (Focus Order). Not yet verified.
- [ ] Tab and Shift+Tab are the way through the panel's contents. Per WCAG 2.1.1 (Keyboard). Not yet verified. Arrow-key navigation between items isn't the popover's job: `craft-action-menu` adds it.
- [ ] Opening the popover doesn't move focus. With a slotted invoker, the panel comes right after it in the shadow tree, so the next Tab moves into the panel's first focusable element. With `for` or `anchor`, the panel's place in the Tab order is wherever the `<craft-popover>` element sits. Per WCAG 2.4.3 (Focus Order) and 1.3.2 (Meaningful Sequence). Not yet verified. Being lifted into the top layer doesn't change the Tab order.
- [ ] A closed popover's content can't be reached by Tab. Lion hides its wrapper with `display: none`. Per WCAG 2.4.3 (Focus Order). Not yet verified.
- [ ] The popover doesn't trap focus: Tab and Shift+Tab can leave the panel. Per WCAG 2.1.2 (No Keyboard Trap). Not yet verified.
- [ ] A panel that overflows its `max-height` and holds no focusable element can still be scrolled from the keyboard. Per WCAG 2.1.1 (Keyboard). Not yet verified. It depends on the browser making scroll containers focusable.

### Visual

- [ ] The panel's 1px border (`--c-color-neutral-border-quiet`) marks its edge against the page, since the shadow that also does so disappears in forced colors. Whether that border needs 3:1 under WCAG 1.4.11 (Non-text Contrast) depends on whether the edge is needed to identify the panel. Not yet verified.
- [ ] In forced colors mode, the panel keeps a visible border and a `Canvas` background, so it doesn't merge with the page behind it. Not yet verified.
- [ ] The popover opens only on activation, never on hover alone, so WCAG 1.4.13 (Content on Hover or Focus) doesn't apply. This holds for the default click interaction (`withDropdownConfig()`).

### Reflow and zoom

- [ ] The panel stays inside the viewport at 320px wide and at 400% zoom: Popper flips and shifts it, it's capped at `20rem` wide and `40vh` (or `--popover-max-block-size`) tall, and longer content scrolls inside it. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Not yet verified. The `20rem` cap is close to a 320px viewport's full width, so an invoker near one edge can push the panel against the other.
- [ ] The panel escapes clipping and scrolling ancestors, as well as stacking contexts such as a sticky footer, so its content isn't cut off or hidden. It's positioned `fixed` and lifted into the top layer. Per WCAG 1.4.10 (Reflow). Not yet verified. `stacks at the popover layer token` in `popover.test.ts` checks the z-index it's given, not that it ends up on top.

## Depends on usage

- 4.1.2 (Name, Role, Value): set `label` to name the panel. See [Accessibility](./Popover.mdx#accessibility).
- 4.1.2 (Name, Role, Value) and 2.1.1 (Keyboard): use a real button as the invoker. See [Accessibility](./Popover.mdx#accessibility).
- 2.4.3 (Focus Order): with `for` or `anchor`, place `<craft-popover>` right after its trigger. See [Accessibility](./Popover.mdx#accessibility).