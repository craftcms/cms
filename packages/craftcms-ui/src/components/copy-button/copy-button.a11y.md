# `craft-copy-button` accessibility

Follows the WAI-ARIA APG [Button](https://www.w3.org/WAI/ARIA/apg/patterns/button/) pattern. `tooltip-label` is shown in a `craft-tooltip`; see `tooltip.a11y.md` for how the tooltip itself behaves.

## Requirements

### Semantics

- [ ] The control is a `craft-button` with `type="button"`, so it never submits a form. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [x] `disabled` disables the `craft-button`, so it leaves the Tab order and doesn't copy. Per WCAG 4.1.2 (Name, Role, Value). Verified by `does nothing while disabled` in `copy-button.test.ts`.
- [ ] The accessible name says what will be copied. Per WCAG 2.4.6 (Headings and Labels) and 4.1.2 (Name, Role, Value). Known gap: an icon-only button is named "Copy to clipboard", whatever it copies. `tooltip-label` goes in the tooltip, which describes the button rather than naming it. A button with slotted content is named by that content.
- [ ] Every string the button shows or announces is translated. Per WCAG 3.1.2 (Language of Parts). "Copy to clipboard", "Copied", "Copy failed" and "Copy" all go through `t()`. Not yet verified.
- [x] After showing the outcome, the tooltip goes back to the `tooltip-label` it started with. Per WCAG 2.4.6 (Headings and Labels). Verified by `restores its tooltip label after showing the outcome` in `copy-button.test.ts`.

### Status

- [ ] A successful or failed copy is announced through a live region. Per WCAG 4.1.3 (Status Messages). Known gap: only the tooltip text and the icon change. The tooltip is a description, which screen readers don't re-read when it changes, and it's only shown on hover or focus. `craft-copy` and `craft-error` are fired, as `announces the copy so a page can react to it` and `announces a failure rather than swallowing it` in `copy-button.test.ts` check, but nothing announces them.
- [ ] The success and failure icons have a text alternative. Per WCAG 1.1.1 (Non-text Content). Known gap: the check and cross are the button's icon, with no label, so they're hidden from assistive technology.
- [ ] Success and failure are told apart by icon shape (a check or a cross) and tooltip text, not by color. Per WCAG 1.4.1 (Use of Color). Not yet verified.

### Keyboard and focus

- [ ] Enter and Space copy the value, and focus stays on the button while the outcome shows and after it clears. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Not yet verified.
- [ ] A keyboard-focused button shows a visible indicator at 3:1 against its surface. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). It uses `craft-button`'s focus ring. Not yet verified.

### Visual

- [ ] The icons are filled with `currentColor`, so they follow the text color and forced colors mode. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] The button is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). An icon-only button is a `small` `craft-button` square by default. Not yet verified.

## Depends on usage

- 2.4.6 (Headings and Labels): put what's copied in the button's visible text. See [Accessibility](./CopyButton.mdx#accessibility).
