# `craft-button-group` accessibility

A group that holds a single selection follows the keyboard behavior of the WAI-ARIA APG [Radio Group](https://www.w3.org/WAI/ARIA/apg/patterns/radio/) pattern. One that allows several selections follows the [Toolbar](https://www.w3.org/WAI/ARIA/apg/patterns/toolbar/) pattern's toggle buttons. A group of action buttons, such as a primary button and its menu, isn't a composite widget, and each button keeps its own tab stop.

**Not for** (use `craft-tabs` instead):

- Buttons that switch between views, where one view is always showing.

## Requirements

### Keyboard and focus

- [x] A group that holds a selection (`name` is set, it has `role="radiogroup"`, or its buttons carry `aria-pressed`) is a single tab stop. The selected button has `tabindex="0"` and the others `-1`, or the first button has it when nothing is selected. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Verified by `makes the selected button the only tab stop` and `makes the first button the tab stop when nothing is selected` in `button-group.test.ts`.
- [x] In a single-selection group, the arrow keys select and focus the next or previous button, wrapping at both ends, and Home and End select the first and last. Per WCAG 2.1.1 (Keyboard). Verified by `selects and focuses the next button with the arrow keys, wrapping` and `jumps to the ends with Home and End` in `button-group.test.ts`.
- [x] The keys select by clicking the button, so a consumer that manages the selection itself, rather than through `name`, follows along. Verified by `clicks the button it lands on, for a consumer that selects` in `button-group.test.ts`.
- [x] In a multi-selection group, the arrow keys move focus without changing the selection, and the tab stop follows focus. Space and Enter toggle the focused button. Per WCAG 2.1.1 (Keyboard). Verified by `moves focus without selecting in a multi-select group` in `button-group.test.ts`.
- [x] Disabled buttons are skipped. Verified by `skips disabled buttons` in `button-group.test.ts`.
- [x] Left and Right are reversed in right-to-left text. Verified by `reverses the left and right arrow keys in right-to-left text` in `button-group.browser.test.ts`.
- [x] A group of action buttons is left alone: each button stays in the Tab order and the arrow keys do nothing. Verified by `leaves a group of action buttons alone` in `button-group.test.ts`.
- [x] Clicking the selected button of a single-selection group again keeps it selected. Verified by `keeps the selected button selected when it’s clicked again` in `button-group.test.ts`.

### Visual

- [x] Adjacent buttons share one border. The hovered, selected, pressed or focused button is drawn above its neighbors, so its whole border and focus ring show. Verified by `overlaps adjacent buttons’ borders, with no gap` and `raises the selected button, so its whole border shows` in `button-group.browser.test.ts`.
- [x] The selected button of a single-selection group doesn't change on hover or press, and has a default cursor, since clicking it does nothing. Verified by the `gives a … group’s selected button a … cursor` cases in `button-group.browser.test.ts`.
- [ ] The selected button is distinguishable from the others in forced colors mode. Not yet verified.

### Semantics

- [ ] Buttons in a single-selection group are exposed as radios (`role="radio"` with `aria-checked`). They're currently buttons with `aria-pressed` inside a `radiogroup`, which matches the keyboard behavior but not the roles. Not yet done.
