# `craft-action-menu` accessibility

Follows the WAI-ARIA APG [Disclosure Navigation Menu](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/examples/disclosure-navigation/) example: a button with `aria-expanded` shows or hides a list of links and buttons that keep their native roles. Tab and Shift+Tab move through the open list, Escape closes it and returns focus to the button, and there is no `menu` role. It adds the example's optional arrow-key navigation: ArrowUp and ArrowDown wrap through the items, and Home and End jump to the ends.

Built on `craft-popover`; see `popover.a11y.md` for the panel's open and close behavior, its focus handling, and its positioning. This file covers only what the menu adds.

**Not for:**

- Choosing a value for a form field. Use `craft-select`, `craft-select-rich` or `craft-combobox`.
- Arbitrary content such as settings or a form. Use `craft-popover`.
- Switching between views where one is always showing. Use `craft-tabs`.
- Content that must block the page until it's dismissed. Use `craft-dialog`.

A change that needs any of these is a different pattern. Don't add a mode to `craft-action-menu` for it.

## Requirements

### Semantics

- [ ] The items keep their native button and link roles, and the content container gets `role="none"` instead of `menu`, as in the example. Per WCAG 4.1.2 (Name, Role, Value) and 1.3.1 (Info and Relationships). Not yet verified.
- [ ] The invoker announces the popup it opens accurately. Per WCAG 4.1.2 (Name, Role, Value). Known gap: the menu sets `aria-haspopup="true"` on the invoker, which means `menu`, while the panel is not a menu (Lion gives it `role="dialog"`; see `popover.a11y.md`). The example sets no `aria-haspopup`.
- [ ] The invoker points at the content with `aria-controls`, as in the example. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] The generated invoker is named by `label` (default "Actions"), through its icon's label, and the same `label` names the panel. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] With `disabled`, the invoker carries `aria-disabled="true"` (and the generated one is a disabled button), and the menu doesn't open by click or keyboard. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified. The `DataDrivenDisabled` and `SlotBasedDisabled` stories have no `play()` assertion.
- [x] The search input is named "Search" (translated) through `aria-label`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `inserts a search input at the top of the content when searchable` in `action-menu.test.ts`.
- [ ] The search input has a visible label that stays while text is entered. Per WCAG 3.3.2 (Labels or Instructions). Known gap: its only visible label is the placeholder, which disappears as soon as someone types.
- [ ] Group headings are tied to the items they label. Per WCAG 1.3.1 (Info and Relationships). Known gap: a group heading is a plain `div` with `role="presentation"`, so it's read as loose text and the group isn't exposed.
- [ ] The number of items left after filtering is announced. Per WCAG 4.1.3 (Status Messages). Known gap: the filter changes the list silently.

### Keyboard and focus

- [ ] Where focus goes when the menu opens. Per WCAG 2.4.3 (Focus Order). Decision TBD: the Disclosure Navigation Menu example leaves focus on the invoker, while the code (`_onOverlayShow`) calls `focus()` on the search input when `searchable`, or on the first navigable item otherwise. Not yet verified in a browser. `focuses the first navigable item on open in non-searchable mode` in `action-menu.test.ts` stubs each item's `focus()`, so it shows the call is made, not that focus moves, and in the `Default` story focus doesn't appear to move to the first item. Possible causes, not yet investigated:
  - Lion dispatches `show` before the items can take focus.
  - The first item renders as a link that `delegatesFocus` doesn't reach.
  - Focus moves only when the menu is opened from the keyboard, not by a click.
- [x] ArrowDown and ArrowUp move to the next or previous item, wrapping at both ends. Per WCAG 2.1.1 (Keyboard). Verified by `ArrowDown/ArrowUp move between items and wrap at the ends` in `action-menu.test.ts`.
- [x] Home and End move to the first and last item. Per WCAG 2.1.1 (Keyboard). Verified by `Home/End jump to the first/last navigable item` in `action-menu.test.ts`.
- [x] Arrow navigation skips hidden, filtered-out and disabled items. Per WCAG 2.1.1 (Keyboard). Verified by `skips consumer-hidden, filtered, and disabled items` in `action-menu.test.ts`.
- [x] From the search input, ArrowDown moves to the first item and ArrowUp to the last. Per WCAG 2.1.1 (Keyboard). Verified by `ArrowDown from the search input focuses the first navigable item` and `ArrowUp from the search input focuses the last navigable item` in `action-menu.test.ts`.
- [x] In a searchable menu, typing a character or Backspace on a focused item returns focus to the search input and applies the keystroke there. Modified keys, Enter and Tab keep their usual behavior. Per WCAG 2.1.1 (Keyboard). Verified by `returns focus to the search input when typing on a focused item` and `does not redirect modified or non-printable keys to the search input` in `action-menu.test.ts`.
- [x] Without `searchable`, typing on an item does nothing, and the arrow keys still navigate. Per WCAG 2.1.1 (Keyboard). Verified by `does nothing on typing keys in non-searchable mode` in `action-menu.test.ts`.
- [x] Escape in a search input that holds text clears the filter rather than closing the menu. Per WCAG 2.1.1 (Keyboard). Verified by `clears the filter on Escape without closing the menu` in `action-menu.test.ts`, which doesn't open the menu first, so it asserts the cleared filter but not that the menu stays open.
- [ ] Escape on an item closes the menu and returns focus to the invoker. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Not yet verified. The menu relies on `craft-popover` for this; see `popover.a11y.md`.
- [ ] Space activates a link item, as Enter does, outside a searchable menu. Per WCAG 2.1.1 (Keyboard). Not yet verified. In a searchable menu, Space types into the search input instead.
- [ ] Activating an item closes the menu and returns focus to the invoker. Per WCAG 2.4.3 (Focus Order). Closing on click is verified by `closes on a click and reports an item the consumer added later` in `action-menu.test.ts`. Where focus goes afterward isn't.
- [ ] Moving focus out of the invoker and the menu closes it, as in the example. Per WCAG 2.4.3 (Focus Order). Known gap, inherited from `craft-popover`; see `popover.a11y.md`.

## Depends on usage

- 4.1.2 (Name, Role, Value): use a real, named button as a slotted invoker. See [Accessibility](./ActionMenu.mdx#accessibility).
- 2.4.6 (Headings and Labels): set a `label` that says whose actions these are when the default "Actions" isn't specific enough. See [Accessibility](./ActionMenu.mdx#accessibility).
- 4.1.2 (Name, Role, Value): build slot-based content from `craft-action-item`, so the items get arrow-key navigation and keep native roles. See [Accessibility](./ActionMenu.mdx#accessibility).
