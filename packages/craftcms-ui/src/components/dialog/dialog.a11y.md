# `craft-dialog` accessibility

Follows the WAI-ARIA APG [Dialog (Modal)](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/) pattern.

**Not for:**

- Content that sits over the page without blocking it, where the person can keep working behind it. Use `craft-popover` instead.
- A region that a button shows or hides in place, in the page flow. Use `craft-disclosure` instead.
- A message that doesn't need a response before the person continues. Use `craft-callout` instead.

A change that needs any of these is a different pattern. Don't add a mode to `craft-dialog` for it.

## Requirements

### Semantics

- [ ] The dialog renders as a native `<dialog>`, so it has the `dialog` role, and opening it uses `showModal()`, which makes it modal (`:modal`) and the rest of the page inert. Per WCAG 4.1.2 (Name, Role, Value) and 1.3.1 (Info and Relationships). Not yet verified for the default mode: the unit tests polyfill `showModal()`, and no test or story asserts that a default dialog matches `:modal`.
- [x] A `label` renders as the header's `<h2>` and names the dialog through `aria-labelledby`, including when the label is set after the first render. Per WCAG 4.1.2 (Name, Role, Value). Verified by `labels the dialog with the title` and `brings the header back when a label is set later` in `dialog.test.ts`.
- [x] Without a `label`, the dialog doesn't point `aria-labelledby` at an empty heading. Per WCAG 4.1.2 (Name, Role, Value). Verified by `labels the dialog only when there is a label to point at` in `dialog.test.ts`.
- [ ] A dialog can be named without a visible title. Per WCAG 4.1.2 (Name, Role, Value). Known gap: `label` is the only way to name the dialog. An `aria-label` or `aria-labelledby` on the host doesn't reach the inner `<dialog>`, so a dialog that can't show a visible title has no name.
- [ ] The header close button is named "Close" (translated) through `aria-label`. Per WCAG 4.1.2 (Name, Role, Value) and 2.5.3 (Label in Name). `renders a close button that closes the dialog` in `dialog.test.ts` clicks the button but doesn't assert its name. Not yet verified.

### Keyboard and focus

- [ ] Opening the dialog moves focus into it: the first focusable element, or an element with `autofocus`. Per WCAG 2.4.3 (Focus Order). The component relies on the browser's dialog focusing steps for this and doesn't set initial focus itself. Not yet verified, including where focus lands when the header has a close button (the first focusable element in the shadow tree) and when the dialog has nothing focusable.
- [ ] Tab and Shift+Tab stay inside an open modal dialog. Per WCAG 2.4.3 (Focus Order). The browser contains focus for `showModal()`. Not yet verified.
- [ ] Closing the dialog returns focus to the element that opened it. Per WCAG 2.4.3 (Focus Order). The component relies on the browser restoring focus when the native `<dialog>` closes and doesn't track the invoker itself. Not yet verified. Removing the host while it's open closes nothing natively, so focus isn't returned in that case.
- [ ] Escape closes a modal dialog, through the same cancelable `craft-before-hide` path as the close button. Per WCAG 2.1.1 (Keyboard). Not yet verified: the native `cancel` event isn't exercised by any test.
- [x] The header close button and any slotted descendant with `data-dialog="close"` close the dialog. Per WCAG 2.1.1 (Keyboard). Verified by `renders a close button that closes the dialog` and `closes when a data-dialog="close" descendant is clicked` in `dialog.test.ts`. Both tests use `click()`, so Enter and Space are covered only because the controls are native buttons.
- [x] A dismissal can be canceled with `craft-before-hide`, so a dialog holding unsaved work can confirm before it closes, and setting `opened` to `false` directly doesn't ask. Verified by `stays open when a listener cancels the dismissal`, `closes when nothing objects`, and `does not ask when opened is set to false directly` in `dialog.test.ts`.
- [ ] The scrolling body is reachable and scrollable from the keyboard when its content has nothing focusable. Per WCAG 2.1.1 (Keyboard). The body has no `tabindex`, so this depends on the browser making scroll containers focusable. The `Scrolling` story has no `play()` assertion. Not yet verified.

### Non-modal mode

**`non-modal` is a workaround, not a second pattern.** It exists only so a dialog can host legacy CP content whose menus append themselves to `<body>` and would otherwise sit beneath the top layer (`VolumeFolderSelectorModal`, the Assets "Move to" picker). It opens the dialog with `show()` instead of `showModal()`, and the component supplies the backdrop, Escape, and Tab containment itself. It looks modal but isn't exposed as modal to assistive technology. Don't use it for new UI: use the default modal dialog, or `craft-popover` for content that shouldn't block the page.

- [x] A `non-modal` dialog opens with `show()`, stays out of the top layer, and renders its own backdrop. Verified by `opens without entering the top layer` and `renders its own backdrop, since ::backdrop only paints for the top layer` in `dialog.test.ts`, and the `NonModal` story, which asserts the dialog doesn't match `:modal`.
- [x] Escape closes a `non-modal` dialog. Per WCAG 2.1.1 (Keyboard). Verified by `closes on Escape, which the platform no longer handles` in `dialog.test.ts`.
- [ ] Tab and Shift+Tab wrap inside an open `non-modal` dialog. Per WCAG 2.4.3 (Focus Order). The wrapping itself is verified by `wraps Tab from the last element to the first` and `wraps Shift-Tab from the first element to the last` in `utilities/focus-trap.test.ts`, but no dialog test checks that the trap is installed on open and released on close.
- [ ] The page behind a `non-modal` dialog is hidden from assistive technology while the dialog is open. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Known gap, and a deviation from the pattern: the dialog has no `aria-modal="true"` and the page isn't made inert, so a screen reader's virtual cursor can leave the dialog even though it looks and behaves as modal for Tab.

### Visual

- [ ] The header close button shows a visible focus indicator at 3:1 against the header surface, in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). The component sets no focus style, so this relies on the browser's default ring. Not yet verified.
- [ ] A focused control inside the dialog isn't hidden behind the header or footer, which are raised over the scrolling body with `z-index`. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)). Not yet verified.
- [ ] The header close button is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified.
- [ ] The dialog surface stays distinguishable from the page in forced colors mode. Known gap: the surface's edge comes from `box-shadow`, which forced colors removes, and it has no border to fall back on. The backdrop's appearance in forced colors is also unverified.
- [x] A modal dialog is centered in the viewport. Verified by `centers a modal dialog in the viewport` in `dialog.browser.test.ts`.

### Reflow and zoom

- [ ] At a 320px viewport and at 200% zoom, the dialog fits the viewport without horizontal scrolling, and long content scrolls inside the body while the header and footer stay reachable. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). The default sizes are capped at `90vw` and `85dvh`. Not yet verified.
- [x] A `fullscreen` dialog fills the viewport in both dimensions. Verified by the `Fullscreen` story.
- [x] A `resizable` dialog is resized by `craft-resize-grips`, whose own requirements cover the grips' keyboard and pointer behavior, and its surface takes the size they report, back to its own size on reset. No grips are offered while `fullscreen`. Per WCAG 1.4.10 (Reflow). Verified by `sizes its surface by its grips, and back to its own size on reset` and `offers no grips while fullscreen` in `dialog.test.ts`.

### Motion

- [ ] Opening and closing don't animate. Per WCAG 2.3.3 (Animation from Interactions). The component defines no transitions. Nothing guards this.

## Depends on usage

- 4.1.2 (Name, Role, Value): set `label` so the dialog has a name. See [Accessibility](./Dialog.mdx#accessibility).
