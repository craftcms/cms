# `craft-button-group` accessibility

A group that holds a selection follows the [exclusive button group](https://lea.verou.me/blog/2022/07/button-group/) pattern: a labeled group of [toggle buttons](https://www.w3.org/WAI/ARIA/apg/patterns/button/), each reporting its state with `aria-pressed`. The buttons keep their role and their keyboard behavior. A group without a `name` is a row of independent action buttons.

**Not for:**

- A form setting that picks one value from a set of options. Use `craft-radio-group` with `buttons`, which is announced and operated as radios.
- Buttons that switch between views, where one view is always showing. Use `craft-tabs`.
- Arrow-key navigation between the buttons. That belongs to a composite widget (radios, tabs, a toolbar), and makes the buttons behave differently from what their role announces.

## Requirements

### Semantics

- [x] A group with a `name` has `role="group"`, so a label given with `aria-labelledby` names it. Its buttons keep the button role and carry `aria-pressed`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `is a group of toggle buttons, each its own tab stop` in `button-group.test.ts`.
- [x] A `role` the consumer sets is kept. Verified by `keeps a role the consumer gave it` in `button-group.test.ts`.

### Keyboard and focus

- [x] Every button is its own tab stop, and is pressed with Space or Enter like any button. Per WCAG 2.1.1 (Keyboard). Verified by `is a group of toggle buttons, each its own tab stop` in `button-group.test.ts`.
- [x] Clicking the selected button of a single-selection group again keeps it selected. Verified by `keeps the selected button selected when it’s clicked again` in `button-group.test.ts`.

### Visual

- [x] Adjacent buttons share one border. The hovered, selected, pressed or focused button is drawn above its neighbors, so its whole border and focus ring show. Verified by `overlaps adjacent buttons’ borders, with no gap` and `raises the selected button, so its whole border shows` in `button-group.browser.test.ts`.
- [x] The selected button of a single-selection group doesn't change on hover or press, and has a default cursor, since clicking it does nothing. Verified by the `gives a … group’s selected button a … cursor` cases in `button-group.browser.test.ts`.
- [ ] The selected button is distinguishable from the others in forced colors mode. Not yet verified.
