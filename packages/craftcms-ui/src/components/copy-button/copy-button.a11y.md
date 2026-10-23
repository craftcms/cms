# `craft-copy-button` accessibility

Follows the WAI-ARIA APG [Button](https://www.w3.org/WAI/ARIA/apg/patterns/button/) pattern. `tooltip-label` is shown in a `craft-tooltip`; see `tooltip.a11y.md` for how the tooltip itself behaves.

## Requirements

### Semantics

- [ ] The control is a native `<button type="button">`, so it never submits a form. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [x] `disabled` disables the native button, so it leaves the Tab order and doesn't copy. Per WCAG 4.1.2 (Name, Role, Value). Verified by `does nothing while disabled` in `copy-button.test.ts`.
- [ ] The accessible name says what will be copied. Per WCAG 2.4.6 (Headings and Labels) and 4.1.2 (Name, Role, Value). Known gap: the name is the slotted content followed by a fixed hidden "Copy to clipboard". `tooltip-label` goes in the tooltip, which describes the button rather than naming it, so every icon-only copy button is named "Copy to clipboard". Slotted text that already says "Copy to clipboard", as in the `Default` story, is read twice.
- [ ] Every string the button shows or announces is translated. Per WCAG 3.1.2 (Language of Parts). Known gap: the hidden "Copy to clipboard", and the "Copied", "Copy failed" and "Copy" tooltip text, are hard-coded in English. Only the initial "Copy" goes through `t()`.
- [ ] After showing the outcome, the tooltip goes back to the `tooltip-label` it started with. Per WCAG 2.4.6 (Headings and Labels). Known gap: it resets to "Copy", dropping a custom `tooltip-label`.

### Status

- [ ] A successful or failed copy is announced through a live region. Per WCAG 4.1.3 (Status Messages). Known gap: only the tooltip text and the icon change. The tooltip is a description, which screen readers don't re-read when it changes, and it's only shown on hover or focus. `craft-copy` and `craft-error` are fired, as `announces the copy so a page can react to it` and `announces a failure rather than swallowing it` in `copy-button.test.ts` check, but nothing announces them.
- [ ] The success and failure icons have a text alternative. Per WCAG 1.1.1 (Non-text Content). Known gap: the check and cross are `craft-icon`s with no `label`, so they're hidden from assistive technology.
- [ ] Success and failure are told apart by icon shape (a check or a cross) and tooltip text, not by color. Per WCAG 1.4.1 (Use of Color). Not yet verified.

### Keyboard and focus

- [ ] Enter and Space copy the value, and focus stays on the button while the outcome shows and after it clears. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Not yet verified.
- [ ] A keyboard-focused button shows a visible indicator at 3:1 against its surface. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). The button sets no focus style, so this relies on the browser's default ring. Not yet verified.

### Visual

- [ ] The icons are filled with `currentColor`, so they follow the text color and forced colors mode. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] The button is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Known gap: the button has no padding, so an icon-only one is about 0.9em tall.

## Depends on usage

- 2.4.6 (Headings and Labels): put what's copied in the button's visible text. See [Accessibility](./CopyButton.mdx#accessibility).
