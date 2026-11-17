# `craft-reorder-button` accessibility

Built on `craft-action-menu`; see `action-menu.a11y.md` for the menu pattern, its keyboard behavior and its focus handling. This file covers only what the reorder button adds.

## Requirements

### Alternatives to dragging

- [x] Choosing "Move down" from the menu emits a composed, bubbling `craft-reorder` event, so an item can be moved with single clicks or the keyboard instead of a drag. Per WCAG 2.5.7 (Dragging Movements) and 2.1.1 (Keyboard). Verified by `emits composed reorder events to list owners` in `reorder-button.test.ts`. No test chooses "Move up" or the horizontal "Move forward"/"Move backward".
- [x] A `nested` button adds "Indent" and "Outdent", so an item can change level in a tree without a drag, and only the allowed one emits `craft-reorder`. Per WCAG 2.5.7 (Dragging Movements). Verified by `only offers indent and outdent on nested buttons` in `reorder-button.test.ts`.
- [x] Each entry in `moves` becomes a menu item named by its `label`, listed after the reorder actions, and choosing it emits `craft-move`. Per WCAG 2.5.7 (Dragging Movements) and 4.1.2 (Name, Role, Value). Verified by `lists moves after the reorder actions and reports the chosen one` in `reorder-button.test.ts`.

### Semantics

- [x] The invoker is a button named by `label`, falling back to a localized "Reorder", and the grip icon adds nothing else to the name. Per WCAG 4.1.2 (Name, Role, Value) and 1.1.1 (Non-text Content). Verified by `names the invoker $name` in `reorder-button.browser.test.ts`.
- [x] The move that would go nowhere (up or forward at `first`, down or backward at `last`, both at `only`) is exposed as disabled. Per WCAG 4.1.2 (Name, Role, Value). Verified by `exposes a move that would go nowhere as disabled at $position` in `reorder-button.browser.test.ts`.
- [x] `disabled` disables the invoker button and sets `aria-disabled="true"` on the host. Per WCAG 4.1.2 (Name, Role, Value). Verified by `disables the invoker and marks the host aria-disabled when disabled` in `reorder-button.browser.test.ts`.
- [x] The arrow icons on the menu items are decorative, so each item's name is its text label alone. Per WCAG 1.1.1 (Non-text Content). Verified by `names each $orientation move by its text alone` in `reorder-button.browser.test.ts`.
- [ ] Horizontal labels describe the direction of travel. Per WCAG 2.4.6 (Headings and Labels). Known gap: "Move forward" moves the item toward the start of the list and "Move backward" toward the end, which reads the opposite way to most people.

### Keyboard and focus

- [x] The invoker is in the Tab order and opens the menu with Enter or Space, unless `disabled`. Per WCAG 2.1.1 (Keyboard). Verified by `is reached with Tab and opens with $key` and `is skipped by Tab when disabled` in `reorder-button.browser.test.ts`.
- [x] Choosing a move, or pressing Escape, closes the menu and leaves focus on the invoker. Per WCAG 2.4.3 (Focus Order). Verified by `keeps focus on the invoker after a move is chosen with Enter`, `keeps focus on the invoker when its list disables the chosen move` and `returns focus to the invoker when Escape closes the menu` in `reorder-button.browser.test.ts`.
- [x] When the list moves the button to a new place in the page while it has focus, focus stays on the invoker. Per WCAG 2.4.3 (Focus Order). Verified by `keeps focus on the invoker when its list moves it` in `reorder-button.browser.test.ts`.
- [x] A keyboard-focused invoker shows a visible ring at 3:1 against the surface, in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Verified by `rings a keyboard-focused invoker at 3:1 against the surface` in `reorder-button.browser.test.ts`.

### Visual

- [x] The invoker is at least 24×24 CSS px at its `small` size. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `is at least 24×24 CSS px` in `reorder-button.browser.test.ts`.
- [x] The grip icon reaches 3:1 against the surface in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Verified by `draws the grip icon at 3:1 against the surface` in `reorder-button.browser.test.ts`.
- [x] In forced colors mode, the grip icon and the focus ring stay visible. Verified by `keeps the grip icon and focus ring visible in forced colors mode` in `reorder-button.browser.test.ts`.

## Depends on usage

- 2.4.6 (Headings and Labels): set `label` to name the item, such as "Reorder Blog post". See [Accessibility](./ReorderButton.mdx#accessibility).
- 4.1.3 (Status Messages): announce the item's new position after a move. See [Accessibility](./ReorderButton.mdx#accessibility).
- 2.4.3 (Focus Order): if the list replaces the moved item's reorder button with a new one, focus the new one. See [Accessibility](./ReorderButton.mdx#accessibility).
- 4.1.2 (Name, Role, Value): keep `position` in step with the item's place in the list. See [Accessibility](./ReorderButton.mdx#accessibility).
