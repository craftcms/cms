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

- [x] The items keep their native button and link roles, and the content container gets `role="none"` instead of `menu`, as in the example. Per WCAG 4.1.2 (Name, Role, Value) and 1.3.1 (Info and Relationships). Verified by `keeps native roles on its items, with no menu around them` in `action-menu.browser.test.ts`.
- [x] The invoker sets no `aria-haspopup`, as in the example. The panel isn't a menu, and `aria-expanded` already tells assistive technology that the button shows and hides it. Per WCAG 4.1.2 (Name, Role, Value). Verified by `tells assistive tech the button expands, not that it opens a menu` in `action-menu.browser.test.ts`.
- [x] The invoker points at the content with `aria-controls`, as in the example. Per WCAG 4.1.2 (Name, Role, Value). Verified by `points the invoker at the content it controls` in `action-menu.browser.test.ts`.
- [x] The generated invoker is named by `label` (default "Actions"), through its icon's label. The panel has no name, as a disclosure panel; see `popover.a11y.md`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `names its generated invoker from label %s` in `action-menu.browser.test.ts`.
- [x] With `disabled`, the invoker carries `aria-disabled="true"` (and the generated one is a disabled button), and the menu doesn't open by click or keyboard. Per WCAG 4.1.2 (Name, Role, Value). Verified by `marks a slotted invoker disabled and stays closed` and `disables a generated invoker` in `action-menu.browser.test.ts`.
- [x] The search input is named "Search" (translated) through `aria-label`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `inserts a search input at the top of the content when searchable` in `action-menu.test.ts`.
- [x] The search input has a visible label that stays while text is entered: the search icon `craft-input-search` puts at its start (see `input-search.a11y.md`). Per WCAG 3.3.2 (Labels or Instructions). Verified by `keeps the search icon as the visible label while text is entered` in `action-menu.browser.test.ts`.
- [ ] Group headings are tied to the items they label. Per WCAG 1.3.1 (Info and Relationships). Known gap: a group heading is a plain `div` with `role="presentation"`, so it's read as loose text and the group isn't exposed.
- [ ] The number of items left after filtering is announced. Per WCAG 4.1.3 (Status Messages). Known gap: the filter changes the list silently.

### Keyboard and focus

- [x] Opening the menu moves focus to the first item it can reach, skipping hidden items but not disabled ones, or to the search input when `searchable`, whether it's opened with Enter, Space or a click. Per WCAG 2.4.3 (Focus Order). A deliberate deviation: the Disclosure Navigation Menu example leaves focus on the button, but moving it in lets the arrow keys work straight away. Verified by `moves focus to the first item it can reach when opened with %s` and `moves focus to the search field when a searchable menu opens` in `action-menu.browser.test.ts`.
- [x] ArrowDown and ArrowUp move to the next or previous item, wrapping at both ends. Per WCAG 2.1.1 (Keyboard). Verified by `ArrowDown/ArrowUp move between items and wrap at the ends` in `action-menu.test.ts`.
- [x] Home and End move to the first and last item. Per WCAG 2.1.1 (Keyboard). Verified by `Home/End jump to the first/last navigable item` in `action-menu.test.ts`.
- [x] Arrow navigation skips hidden and filtered-out items, and stops on disabled ones, so they're announced as unavailable rather than passed over. Per WCAG 2.1.1 (Keyboard) and 4.1.2 (Name, Role, Value). Verified by `skips consumer-hidden and filtered items, but stops on disabled ones` in `action-menu.test.ts`.
- [x] From the search input, ArrowDown moves to the first item and ArrowUp to the last. Per WCAG 2.1.1 (Keyboard). Verified by `ArrowDown from the search input focuses the first navigable item` and `ArrowUp from the search input focuses the last navigable item` in `action-menu.test.ts`.
- [x] In a searchable menu, typing a character or Backspace on a focused item returns focus to the search input and applies the keystroke there. Modified keys, Enter and Tab keep their usual behavior. Per WCAG 2.1.1 (Keyboard). Verified by `returns focus to the search input when typing on a focused item` and `does not redirect modified or non-printable keys to the search input` in `action-menu.test.ts`.
- [x] Without `searchable`, typing on an item does nothing, and the arrow keys still navigate. Per WCAG 2.1.1 (Keyboard). Verified by `does nothing on typing keys in non-searchable mode` in `action-menu.test.ts`.
- [x] Escape in a search input that holds text clears the filter rather than closing the menu. Per WCAG 2.1.1 (Keyboard). Verified by `clears the filter on Escape without closing the menu` in `action-menu.test.ts`, which doesn't open the menu first, so it asserts the cleared filter but not that the menu stays open.
- [x] Escape on an item closes the menu and returns focus to the invoker. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Verified by `closes on Escape from an item and returns focus to the invoker` in `action-menu.browser.test.ts`.
- [x] Space activates a link item, as Enter does, outside a searchable menu. Per WCAG 2.1.1 (Keyboard). Verified by `follows a link item on Space` in `action-menu.browser.test.ts`. In a searchable menu, Space types into the search input instead.
- [x] Activating an item closes the menu and returns focus to the invoker. Per WCAG 2.4.3 (Focus Order). Verified by `closes on a click and reports an item the consumer added later` in `action-menu.test.ts` and `closes and returns focus to a craft-button invoker when an item is chosen with Enter` in `action-menu.browser.test.ts`.
- [x] Moving focus out of the invoker and the menu closes it, as in the example. Per WCAG 2.4.3 (Focus Order) and 2.4.11 (Focus Not Obscured (Minimum)). Verified by `closes when Tab moves focus past its last item` in `action-menu.browser.test.ts`. The behavior comes from `craft-popover`; see `popover.a11y.md`.

### Visual

- [x] The search input's placeholder takes the quiet text color at 4.5:1 against the menu in the light and dark themes, wherever the menu is rendered. Per WCAG 1.4.3 (Contrast (Minimum)). Verified by `sets the search placeholder at 4.5:1 against the menu inside a shadow root in the %s theme` in `action-menu.browser.test.ts`.

## Depends on usage

- 4.1.2 (Name, Role, Value): use a real, named button as a slotted invoker. See [Accessibility](./ActionMenu.mdx#accessibility).
- 2.4.6 (Headings and Labels): set a `label` that says whose actions these are when the default "Actions" isn't specific enough. See [Accessibility](./ActionMenu.mdx#accessibility).
- 4.1.2 (Name, Role, Value): build slot-based content from `craft-action-item`, so the items get arrow-key navigation and keep native roles. See [Accessibility](./ActionMenu.mdx#accessibility).
