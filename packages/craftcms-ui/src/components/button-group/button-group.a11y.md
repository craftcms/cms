# `craft-button-group` accessibility

Without `name`, the group only joins its buttons visually; each one follows `button.a11y.md`. With `name`, the group follows the [exclusive button group](https://lea.verou.me/blog/2022/07/button-group/) pattern: a labeled group of WAI-ARIA APG [toggle buttons](https://www.w3.org/WAI/ARIA/apg/patterns/button/), each reporting its state with `aria-pressed`. The buttons keep their role and their keyboard behavior.

**Not for:**

- A form setting that picks one value from a set of options. Use `craft-radio-group` with `buttons`, which is announced and operated as radios.
- Switching between panels where one is always showing. Use `craft-tabs`.
- Arrow-key navigation between the buttons. That belongs to a composite widget (radios, tabs, a toolbar), and makes the buttons behave differently from what their role announces. No component implements the APG [Toolbar](https://www.w3.org/WAI/ARIA/apg/patterns/toolbar/) pattern yet; raise it rather than adding a mode here.

## Requirements

### Semantics

- [ ] Without `name`, the host has no role, so its buttons are announced as independent buttons. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.
- [x] With `name`, the host has `role="group"`, so a label given with `aria-labelledby` names it. Its buttons keep the button role and carry `aria-pressed`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `is a group of toggle buttons, each its own tab stop` in `button-group.test.ts`.
- [x] A `role` set on the host is kept. Verified by `keeps a role the consumer gave it` in `button-group.test.ts`.
- [x] In single-select mode, each button's `aria-pressed` follows the selected `value`, including a selection given in markup with `active`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `adopts its value from a child marked active in markup` in `button-group.test.ts` and the `SelectionFromMarkup` story.
- [ ] In `multiple` mode, each button's `aria-pressed` follows its `active` state. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified. `toggles multiple selected values` in `button-group.test.ts` checks `active` but not `aria-pressed`.
- [ ] In selection mode, only the group's own options get `aria-pressed`. Per WCAG 4.1.2 (Name, Role, Value). Known gap: every `craft-button` descendant gets it, including a nested action menu's invoker, which is then announced as an unpressed toggle.
- [ ] The group has an accessible name in selection mode. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Known gap: there's no `label` property; the name comes only from `aria-label` or `aria-labelledby` on the host.

### Keyboard and focus

- [x] Every button is its own Tab stop, and is pressed with Space or Enter like any button. The group adds no arrow-key navigation. Per WCAG 2.1.1 (Keyboard). Verified by `is a group of toggle buttons, each its own tab stop` in `button-group.test.ts`.
- [x] Clicking the selected button of a single-selection group again keeps it selected. Verified by `keeps the selected button selected when it’s clicked again` in `button-group.test.ts`.
- [ ] Pressing a button changes the selection without moving focus, and focus stays on the pressed button. Per WCAG 2.4.3 (Focus Order) and 3.2.2 (On Input). Not yet verified.

### Visual

- [x] Adjacent buttons share one border. The hovered, selected, pressed or focused button is drawn above its neighbors, so its whole border and focus ring show. Verified by `overlaps adjacent buttons’ borders, with no gap` and `raises the selected button, so its whole border shows` in `button-group.browser.test.ts`.
- [x] Adjacent filled buttons, which have no visible border of their own, are separated by a divider in a tint of their text color, except on either side of the selected button. It stays visible while either neighbor is hovered, and reaches a button nested in a grouped element, such as an action menu's invoker. Verified by `marks dividers between buttons, but not beside the selected one` in `button-group.test.ts`, and `draws a divider between filled buttons, but not beside the selected one`, `keeps the divider showing when the button before it is hovered` and `draws the divider on a button nested in a grouped element` in `button-group.browser.test.ts`.
- [x] The selected button of a single-selection group doesn't change on hover or press, and has a default cursor, since clicking it does nothing. Verified by the `gives a … group’s selected button a … cursor` cases in `button-group.browser.test.ts`.
- [ ] The selected button is marked by more than its fill and text color. Per WCAG 1.4.1 (Use of Color) and 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] The selected button is distinguishable from the others in forced colors mode. Not yet verified.
- [ ] Each button is at least 24×24 CSS px, since buttons sit next to each other and get no spacing exception. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified. An `xsmall` button's expanded hit area overlaps its neighbors.

<!-- CONFLICT-REVIEW: merged from 6.x. Dropped the "radiogroup with aria-pressed children" known gap, which 0908fc04c1 fixed by switching to role="group". The selected-state item no longer mentions an inset shadow, since 6.x's styles no longer draw one. The target-size item says "next to each other" rather than "1px apart", since 6.x overlaps the borders. -->

## Depends on usage

- 4.1.2 (Name, Role, Value): name the group with `aria-label` or `aria-labelledby` in selection mode. See [Accessibility](./ButtonGroup.mdx#accessibility).
- 4.1.2 (Name, Role, Value): name each icon-only button. See [Icon Buttons](./ButtonGroup.mdx#icon-buttons).
