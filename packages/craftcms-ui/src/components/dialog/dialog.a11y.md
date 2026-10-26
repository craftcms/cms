# `craft-dialog` accessibility

Follows the WAI-ARIA APG [Dialog (Modal)](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/) pattern.

**Not for:**

- Content that sits over the page without blocking it, where the person can keep working behind it. Use `craft-popover` instead.
- A region that a button shows or hides in place, in the page flow. Use `craft-disclosure` instead.
- A message that doesn't need a response before the person continues. Use `craft-callout` instead.

A change that needs any of these is a different pattern. Don't add a mode to `craft-dialog` for it.

## Requirements

### Semantics

- [x] The dialog renders as a native `<dialog>`, so it has the `dialog` role, and opening it uses `showModal()`, which makes it modal (`:modal`) and the rest of the page inert. Per WCAG 4.1.2 (Name, Role, Value) and 1.3.1 (Info and Relationships). Verified by `opens a default dialog as modal` in `dialog.browser.test.ts`.
- [x] A `label` names the dialog through its header heading. Per WCAG 4.1.2 (Name, Role, Value). Verified by `labels the dialog with the title`, `brings the header back when a label is set later`, and `labels the dialog only when there is a label to point at` in `dialog.test.ts`.
- [x] Under `no-header`, the `label` still names the dialog through a hidden heading that takes no space. Per WCAG 4.1.2 (Name, Role, Value). Verified by `drops the header under no-header` and `still labels the dialog under no-header` in `dialog.test.ts`, and `keeps the label as the name under no-header` in `dialog.browser.test.ts`.
- [ ] A dialog can be named by an `aria-label` or `aria-labelledby` on the host. Per WCAG 4.1.2 (Name, Role, Value). Known gap: neither reaches the inner `<dialog>`, so `label` is the only way to name it, with `no-header` for a dialog without a visible title.
- [x] The header close button is named "Close" (translated) through `aria-label`. Per WCAG 4.1.2 (Name, Role, Value) and 2.5.3 (Label in Name). Verified by `names the close button "Close"` in `dialog.test.ts`.

### Keyboard and focus

- [x] Opening the dialog moves focus into it: the first focusable element, or an element with `autofocus`. Per WCAG 2.4.3 (Focus Order). Verified by `moves focus into the dialog on open` and `focuses an autofocus element on open` in `dialog.browser.test.ts`.
- [x] Tab and Shift+Tab stay inside an open modal dialog. Per WCAG 2.4.3 (Focus Order). Verified by `keeps Tab and Shift+Tab inside a modal dialog` in `dialog.browser.test.ts`.
- [x] Closing the dialog returns focus to the element that opened it. Per WCAG 2.4.3 (Focus Order). Verified by `returns focus to the invoker on close` in `dialog.browser.test.ts`.
- [x] When the opener can't take focus by the time the dialog closes, such as an item in a menu that has since closed, focus goes to that menu's button. This holds however the dialog closes, including when focus had left it, and when the inner native `<dialog>` closes by itself. Per WCAG 2.4.3 (Focus Order). Verified by `returns focus to the action menu invoker after a menu item opens it`, `returns focus to the action menu invoker when focus left the dialog before it closed`, and `returns focus to the action menu invoker when the native dialog closes by itself` in `dialog.browser.test.ts`.
- [x] With `returnFocus` set, closing the dialog focuses the element it returns, as long as that element can take focus, including when the inner native `<dialog>` closes by itself. Per WCAG 2.4.3 (Focus Order). Verified by `focuses the element returnFocus returns on close`, `applies returnFocus when the native dialog is closed directly`, and `keeps the native return target when returnFocus returns nothing` in `dialog.browser.test.ts`.
- [x] Closing a `non-modal` dialog leaves focus alone when it's on something outside the dialog, so a dialog closed while someone is working elsewhere doesn't pull focus away. Per WCAG 3.2.1 (On Focus) and 2.4.3 (Focus Order). Verified by `leaves focus alone when it is outside the dialog at close` and `leaves focus on the opener when a non-modal dialog closes natively with focus already outside it` in `dialog.browser.test.ts`.
- [x] Escape closes a modal dialog. Per WCAG 2.1.1 (Keyboard). Verified by `closes on Escape through craft-before-hide` in `dialog.browser.test.ts`.
- [x] A dismissal can be canceled with `craft-before-hide`, so a dialog holding unsaved work can confirm before it closes, and setting `opened` to `false` directly doesn't ask. Verified by `stays open when a listener cancels the dismissal`, `closes when nothing objects`, and `does not ask when opened is set to false directly` in `dialog.test.ts`.
- [x] The header close button and any slotted descendant with `data-dialog="close"` close the dialog. Per WCAG 2.1.1 (Keyboard). Verified by `renders a close button that closes the dialog` and `closes when a data-dialog="close" descendant is clicked` in `dialog.test.ts`. Both tests use `click()`, so Enter and Space are covered only because the controls are buttons: the close button is a `craft-button`, and slotted controls are expected to be native buttons.
- [x] The scrolling body is reachable and scrollable from the keyboard when its content has nothing focusable. Per WCAG 2.1.1 (Keyboard). The body has no `tabindex`, so this depends on the browser making scroll containers focusable. Chromium does, and it focuses the body on open. Verified by `lets the keyboard reach a scrolling body with nothing focusable` in `dialog.browser.test.ts`.

### Non-modal mode

**`non-modal` is a workaround, not a second pattern.** It exists only so a dialog can host legacy CP content whose menus append themselves to `<body>` and would otherwise sit beneath the top layer. It opens the dialog with `show()` instead of `showModal()`, and the component supplies the backdrop, Escape, and Tab containment itself. It looks modal but isn't exposed as modal to assistive technology. Don't use it for new UI: use the default modal dialog, or `craft-popover` for content that shouldn't block the page.

- [x] A `non-modal` dialog opens with `show()`, stays out of the top layer, and renders its own backdrop. Verified by `opens without entering the top layer` and `renders its own backdrop, since ::backdrop only paints for the top layer` in `dialog.test.ts`.
- [x] Escape closes a `non-modal` dialog. Per WCAG 2.1.1 (Keyboard). Verified by `closes on Escape, which the platform no longer handles` in `dialog.test.ts`.
- [x] Tab and Shift+Tab wrap inside an open `non-modal` dialog. Per WCAG 2.4.3 (Focus Order). The wrapping itself is verified by `wraps Tab from the last element to the first` and `wraps Shift-Tab from the first element to the last` in `utilities/focus-trap.test.ts`. Installing the trap on open and releasing it on close is verified by `contains Tab in a non-modal dialog and releases it on close` in `dialog.browser.test.ts`.
- [ ] The page behind a `non-modal` dialog is hidden from assistive technology while the dialog is open. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Known gap, and a deviation from the pattern: the dialog has no `aria-modal="true"` and the page isn't made inert, so a screen reader's virtual cursor can leave the dialog even though it looks and behaves as modal for Tab.

### Visual

- [ ] The header close button shows a visible focus indicator at 3:1 against the header surface, in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). The close button is a `craft-button`, so it uses that component's focus ring. Not yet verified.
- [x] A focused control inside the dialog isn't hidden behind the header or footer, which are raised over the scrolling body with `z-index`. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)). Verified by `scrolls a focused control clear of the footer` and `scrolls a focused control clear of the header` in `dialog.browser.test.ts`.
- [x] The header gives the close button at least 24px of vertical room, so it meets the target size minimum through the spacing exception. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `gives the close button 24px of vertical room in the header` in `dialog.browser.test.ts`.
- [x] A dialog that overlays page content has an outline that keeps it distinguishable from the page in forced colors mode. The outline is a 1px transparent `outline`, which forced colors paints in a system color while removing the `box-shadow` ring. A `fullscreen` dialog has no adjacent page content, so it needs no outline. Verified by `outlines the surface when it overlays the page` in `dialog.browser.test.ts`.

- [x] A modal dialog is centered in the viewport. Verified by `centers a modal dialog in the viewport` in `dialog.browser.test.ts`.

### Reflow and zoom

- [x] At a 320px viewport and at 200% zoom, the dialog fits the viewport without horizontal scrolling, and long content scrolls inside the body while the header and footer stay reachable. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). The default sizes are capped at `90vw` and `85dvh`. Verified at 320px by `fits a 320px viewport without horizontal scroll` in `dialog.browser.test.ts`. That's the CSS width of a 1280px viewport at 400% zoom, so it covers 200%.
- [x] A `fullscreen` dialog fills the viewport in both dimensions. Verified by the `Fullscreen` story.
- [x] A `resizable` dialog is resized by `craft-resize-grips`, whose own requirements cover the grips' keyboard and pointer behavior, and its surface takes the size they report, back to its own size on reset. No grips are offered while `fullscreen`. Per WCAG 1.4.10 (Reflow). Verified by `sizes its surface by its grips, and back to its own size on reset` and `offers no grips while fullscreen` in `dialog.test.ts`.

### Motion

- [x] Opening and closing don't animate. Per WCAG 2.3.3 (Animation from Interactions). The component defines no transitions. Verified by `does not animate opening or closing` in `dialog.browser.test.ts`.

## Depends on usage

- 4.1.2 (Name, Role, Value): set `label` so the dialog has a name. See [Accessibility](./Dialog.mdx#accessibility).
- 2.5.8 (Target Size (Minimum)): keep other targets clear of the close button. See [Accessibility](./Dialog.mdx#accessibility).
