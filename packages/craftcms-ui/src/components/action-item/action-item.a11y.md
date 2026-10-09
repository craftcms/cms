# `craft-action-item` accessibility

Used inside `craft-action-menu`; see `action-menu.a11y.md` for the pattern. This file covers only the item itself.

## Requirements

### Semantics

- [x] The item renders a native `<button>`, or an `<a href>` when `href` is set, so it keeps the button or link role. Per WCAG 4.1.2 (Name, Role, Value). Verified by `marks its own button as current, not just the host` in `action-item.test.ts` and `carries target and rel onto a link item’s anchor` in `action-menu.test.ts`.
- [ ] The default slot is the item's accessible name, and an `icon` without a label stays out of it as decorative. Per WCAG 4.1.2 (Name, Role, Value) and 1.1.1 (Non-text Content). Not yet verified.
- [x] `current` sets `aria-current` on the item's own button, where an attribute on the host wouldn't reach. Per WCAG 4.1.2 (Name, Role, Value). Verified by `marks its own button as current, not just the host` in `action-item.test.ts`. No test covers a link item.
- [ ] A link to the current page is marked `aria-current="page"`, as in the Disclosure Navigation Menu example. Per WCAG 4.1.2 (Name, Role, Value). Known gap: `current` always sets `aria-current="true"`.
- [ ] A disabled item is exposed as disabled. Per WCAG 4.1.2 (Name, Role, Value). A button item gets the native `disabled` attribute. Known gap: a link item gets neither `disabled` nor `aria-disabled`, so it stays in the Tab order and is announced as an ordinary link, though clicking it does nothing.
- [ ] A `type="checkbox"` item exposes whether it's checked. Per WCAG 4.1.2 (Name, Role, Value) and 1.3.1 (Info and Relationships). Known gap: `checked` only shows a check icon. The item has no `role` or `aria-checked`, so the state isn't announced.
- [x] Anything focusable slotted into `suffix` is flagged with the `a11y-error` outline, since it would nest a control inside the item's button. Static suffix content isn't flagged. Per WCAG 4.1.2 (Name, Role, Value). Verified by `a button in the suffix is flagged`, `a native control in the suffix is flagged`, `a link in the suffix is flagged`, `a plain suffix is not flagged` and `an empty suffix is not flagged` in `action-item.test.ts`.
- [ ] The progress of an `action` is announced: loading, then success or failure. Per WCAG 4.1.3 (Status Messages). Known gap: the spinner, check, cross and feedback message replace the icon and label with no live region, and the check and cross have no text alternative when no feedback message is set.

### Keyboard and focus

- [ ] Focusing the host moves focus to its internal button or link (`delegatesFocus`), which is what the menu's arrow keys rely on. Per WCAG 2.1.1 (Keyboard). Not yet verified. The menu's tests stub `focus()`.
- [ ] Enter and Space activate a button item, and Enter activates a link item. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] A keyboard-focused item shows a visible indicator at 3:1 against the menu surface, in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). The item sets no focus style, so this relies on the browser's default ring. Not yet verified.

### Visual

- [ ] An `active` item is marked by a border as well as a background color. Per WCAG 1.4.1 (Use of Color) and 1.4.11 (Non-text Contrast). Not yet verified. The `Active` story has no `play()` assertion.
- [ ] A `danger` item's text meets 4.5:1 against the menu surface, at rest and on hover. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified.
- [ ] Every item is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified.
- [ ] In forced colors mode, the focus indicator and the `active` border stay visible. Not yet verified.

### Reflow and zoom

- [ ] The full label stays readable at a 320px viewport and at 200% zoom. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Known gap: the label is kept to one line and truncated with an ellipsis, and the cut-off text can't be revealed.

## Depends on usage

- 4.1.2 (Name, Role, Value): give an icon-only item a text label. See [Accessibility](./ActionItem.mdx#accessibility).
- 4.1.2 (Name, Role, Value): put no controls in the `suffix` slot. See [Accessibility](./ActionItem.mdx#accessibility).
- 1.4.1 (Use of Color): name a destructive action in its label as well as using `variant="danger"`. See [Accessibility](./ActionItem.mdx#accessibility).
- 1.1.1 (Non-text Content): say in the label when a link opens in a new window, since the item's `icon` is always decorative. See [Accessibility](./ActionItem.mdx#accessibility).
