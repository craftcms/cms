# `craft-info-icon` accessibility

Follows the toggletip pattern described in Heydon Pickering's [Tooltips & Toggletips](https://inclusive-components.design/tooltips-toggletips/). There's no APG toggletip pattern. A button press shows short, non-interactive help text, which stays open until it's dismissed. The bubble is a `craft-tooltip` with `trigger="click"`; see `tooltip.a11y.md` for its visual requirements.

**Not for:**

- Content with links, buttons, form controls or anything else focusable. Use `craft-popover`.
- A hint that should appear on hover or keyboard focus, without a press. Use `craft-tooltip`.
- Information someone needs before acting. Use visible help text, such as `craft-field`'s instructions.

## Requirements

### Semantics

- [x] The trigger is a button (`craft-button`, `type="button"`) holding only the icon. Per WCAG 4.1.2 (Name, Role, Value). Verified by `renders a button carrying the icon` in `info-icon.test.ts`.
- [x] The button's accessible name comes from the icon's `label`, which defaults to a translated "More Info" and can be replaced with `label`. Per WCAG 4.1.2 (Name, Role, Value) and 1.1.1 (Non-text Content). Verified by `names the icon-only button` and `takes a label of its own` in `info-icon.test.ts`.
- [x] The bubble is wired to the button by a shared `id`, so it describes the button that opens it. Per WCAG 1.3.1 (Info and Relationships). Verified by `wires the tooltip to the button` in `info-icon.test.ts`.
- [ ] The button exposes whether the bubble is open with `aria-expanded`. Per WCAG 4.1.2 (Name, Role, Value). Known gap: the click-triggered tooltip sets no `aria-expanded`.
- [ ] The bubble's text is announced when the button opens it, as in the toggletip pattern, which renders the text into a live region. Per WCAG 4.1.3 (Status Messages). Known gap: the text is only referenced with `aria-describedby`, which most screen readers read when focus lands on the button, not when the bubble opens.
- [ ] `disabled` stops the bubble opening, and the button is exposed as disabled. Per WCAG 4.1.2 (Name, Role, Value). Known gap: `disabled` is reflected on the host but passed to neither the button nor the tooltip, so the `Disabled` story still opens.

### Keyboard and pointer

- [x] Activating the button toggles the bubble: the first press opens it, the second closes it. Per WCAG 2.1.1 (Keyboard). Verified for the tooltip's click trigger by `toggles on invoker click when trigger includes click` in `tooltip.test.ts`, which uses `button.click()`. No test presses Enter or Space on the info icon's button.
- [ ] The bubble opens only on a press, never on hover or focus, so WCAG 1.4.13 (Content on Hover or Focus) doesn't apply. `trigger="click"` removes the tooltip's hover and focus interaction. Not yet verified.
- [ ] Escape closes the bubble while focus is on the button, and focus stays there. Per WCAG 2.1.1 (Keyboard). Lion's tooltip config sets `hidesOnEsc`; not yet verified.
- [ ] A click outside the button and bubble closes it. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)). Known gap: the tooltip doesn't set `hidesOnOutsideClick`.
- [ ] Moving focus away from the button closes the bubble, so it doesn't cover the next focused control. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)). Known gap: nothing closes it on blur.
- [ ] Opening one info icon closes any other that's open. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)). Not yet verified. The `OnlyOneOpen` story has no `play()` assertion.
- [ ] Opening and closing the bubble doesn't move focus. Per WCAG 3.2.1 (On Focus). Not yet verified.

### Visual

- [ ] The button is at least 24×24 CSS px, or has enough space around it. Per WCAG 2.5.8 (Target Size (Minimum)). Known gap: it uses `size="zero"`, which sets no height or padding and has no minimum hit area, so the target is the icon's own size.
- [ ] The button shows a focus indicator at 3:1 against its surroundings, in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified for the `plain`, `zero` button.
- [ ] The icon reaches 3:1 against the surface it sits on, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] In forced-colors mode, the icon and focus indicator stay visible. Not yet verified.

## Depends on usage

- 2.4.6 (Headings and Labels): set `label` to name what the icon explains, such as "More info about propagation", when a page holds more than one. See [Accessibility](./InfoIcon.mdx#accessibility).
- 1.3.1 (Info and Relationships): put only plain text in the default slot. See [Accessibility](./InfoIcon.mdx#accessibility).
