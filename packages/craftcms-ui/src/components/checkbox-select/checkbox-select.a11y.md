# `craft-checkbox-select` accessibility

Follows the WAI-ARIA APG [Checkbox](https://www.w3.org/WAI/ARIA/apg/patterns/checkbox/) pattern for a set of checkboxes, in a group named by its label. Each option is a `craft-checkbox`; see `checkbox.a11y.md`. Reordering uses `craft-reorder-button`; see `reorder-button.a11y.md`. The “Add item” menu is a `craft-action-menu`; see `action-menu.a11y.md`.

**Not for:**

- Picking exactly one of several options. Use `craft-radio-group`.
- Picking from a long list to type and filter. Use `craft-combobox`.
- Plain checkboxes with no order, “All” option, or “Add item” menu. Use `craft-checkbox-group`.

## Requirements

### Semantics

- [x] With a `label`, the options sit in a `role="group"` named by it through `aria-labelledby`. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `names its group with its label` in `checkbox-select.browser.test.ts`.
- [x] In the `add-menu` presentation, each Remove button is named “Remove {label}”, so the buttons can be told apart. Per WCAG 2.4.6 (Headings and Labels) and 4.1.2 (Name, Role, Value). Verified by `removes an option in add-menu, moving focus to the next one` in `checkbox-select.browser.test.ts`.
- [x] Disabled options can't be removed or moved, and `disabled` takes away every Remove button and the “Add item” menu. Per WCAG 4.1.2 (Name, Role, Value). Verified by `keeps disabled options checked and in place` and `drops the “Add item” menu once everything is checked, and when disabled` in `checkbox-select.browser.test.ts`.

### Keyboard and focus

- [x] Every move that can be made by dragging can also be made with the option's reorder button, whose menu moves it up or down. Per WCAG 2.1.1 (Keyboard) and 2.5.7 (Dragging Movements). Verified by `moves options with their reorder buttons, posting the new order` in `checkbox-select.browser.test.ts`.
- [x] After an option is moved with its reorder button, focus stays on that button, in the option's new place. Per WCAG 2.4.3 (Focus Order). Verified by `keeps focus on an option’s reorder button after moving it` in `checkbox-select.browser.test.ts`.
- [x] After an option is removed, focus moves to the next option's Remove button, or else the previous one's, or else the “Add item” button. Per WCAG 2.4.3 (Focus Order). Verified by `removes an option in add-menu, moving focus to the next one` in `checkbox-select.browser.test.ts`.
- [x] When the last option is added and the “Add item” menu goes away, focus moves to the new option's Remove button. Per WCAG 2.4.3 (Focus Order). Verified by `moves focus to the new option once nothing is left to add` in `checkbox-select.browser.test.ts`.

### Status messages

- [x] Adding, removing, and moving an option is announced through a polite status region. Per WCAG 4.1.3 (Status Messages). Verified by `lists only checked options in add-menu, offering the rest to add`, `removes an option in add-menu, moving focus to the next one`, and `moves options with their reorder buttons, posting the new order` in `checkbox-select.browser.test.ts`.

### Visual

- [ ] The line showing where a dragged option will land is a border, so it keeps a system color in forced colors mode. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): name the group, with `label` or an enclosing `craft-field` with `fieldset`. See [Accessibility](./CheckboxSelect.mdx#accessibility).
