# `craft-reorder-button` accessibility

Built on `craft-action-menu`; see `action-menu.a11y.md` for the menu pattern, its keyboard behavior and its focus handling. This file covers only what the reorder button adds.

## Requirements

### Alternatives to dragging

- [x] Choosing "Move down" from the menu emits a composed, bubbling `craft-reorder` event, so an item can be moved with single clicks or the keyboard instead of a drag. Per WCAG 2.5.7 (Dragging Movements) and 2.1.1 (Keyboard). Verified by `emits composed reorder events to list owners` in `reorder-button.test.ts`. No test chooses "Move up" or the horizontal "Move forward"/"Move backward".
- [x] A `nested` button adds "Indent" and "Outdent", so an item can change level in a tree without a drag, and only the allowed one emits `craft-reorder`. Per WCAG 2.5.7 (Dragging Movements). Verified by `only offers indent and outdent on nested buttons` in `reorder-button.test.ts`.
- [x] Each entry in `moves` becomes a menu item named by its `label`, listed after the reorder actions, and choosing it emits `craft-move`. Per WCAG 2.5.7 (Dragging Movements) and 4.1.2 (Name, Role, Value). Verified by `lists moves after the reorder actions and reports the chosen one` in `reorder-button.test.ts`.

### Semantics

- [ ] The invoker is a button named by `label`, falling back to a localized "Reorder", and the grip icon adds nothing else to the name. Per WCAG 4.1.2 (Name, Role, Value) and 1.1.1 (Non-text Content). Not yet verified.
- [ ] The move that would go nowhere (up or forward at `first`, down or backward at `last`, both at `only`) is exposed as disabled. Per WCAG 4.1.2 (Name, Role, Value). Partly verified: `blocks both moves for an only child` in `reorder-button.test.ts` asserts that neither move emits an event, but not that the items are disabled.
- [ ] `disabled` disables the invoker button and sets `aria-disabled="true"` on the host. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] The arrow icons on the menu items are decorative, so each item's name is its text label alone. Per WCAG 1.1.1 (Non-text Content). Not yet verified.
- [ ] Horizontal labels describe the direction of travel. Per WCAG 2.4.6 (Headings and Labels). Known gap: "Move forward" moves the item toward the start of the list and "Move backward" toward the end, which reads the opposite way to most people.

### Keyboard and focus

- [ ] The invoker is in the Tab order and opens the menu with Enter or Space, unless `disabled`. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] A keyboard-focused invoker shows a visible ring at 3:1 against the surface, in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified.

### Visual

- [ ] The invoker is at least 24×24 CSS px at its `small` size. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified.
- [ ] The grip icon reaches 3:1 against the surface in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] In forced colors mode, the grip icon and the focus ring stay visible. Not yet verified.

## Depends on usage

- 2.4.6 (Headings and Labels): set `label` to name the item, such as "Reorder Blog post". See [Accessibility](./ReorderButton.mdx#accessibility).
- 4.1.3 (Status Messages): announce the item's new position after a move. See [Accessibility](./ReorderButton.mdx#accessibility).
- 2.4.3 (Focus Order): keep focus on the moved item's reorder button after the list re-renders. See [Accessibility](./ReorderButton.mdx#accessibility).
- 4.1.2 (Name, Role, Value): keep `position` in step with the item's place in the list. See [Accessibility](./ReorderButton.mdx#accessibility).
