# `craft-nav-list` accessibility

Follows the WAI-ARIA APG [Disclosure Navigation Menu](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/examples/disclosure-navigation/) example, in its variant with top-level links: a list of links, where a branch with a subnav keeps its own link and gets a separate button carrying `aria-expanded` to show the subnav, indented beneath it or in a flyout beside it.

- **List:** a plain list of links and buttons. No `menu` or `menubar` role, and no arrow-key navigation: Tab moves through the items in order.
- **Branch:** the subnav is a nested `craft-nav-list` in a `craft-nav-item`'s `subnav` slot, shown or hidden by that item's toggle.

**Not for:**

- A list of actions or commands that pops up from a button. Use `craft-action-menu`.
- Switching between views of the same content, where one view is always showing. Use `craft-tabs` — including when the strip is to look like one, which `appearance="tabs"` only draws.
- The trail of ancestors leading to the current page. Use `craft-breadcrumbs`.
- A region of content, rather than navigation, that expands in place. Use `craft-disclosure`.

Don't add `menu` roles or roving arrow-key focus to the list to make it behave like an application menu.

## Requirements

### Semantics

- [x] The list renders a `<ul>` around its slotted items, so a screen reader announces them as one list with a count. Per WCAG 1.3.1 (Info and Relationships). Verified by `renders a list around its items` and `takes its items from the default slot` in `nav-list.test.ts`.
- [ ] Each slotted `craft-nav-item` is exposed as a list item of that `<ul>` across the shadow boundary, and a nested list in an item's `subnav` slot is exposed as a list inside that item. Per WCAG 1.3.1 (Info and Relationships). Not yet verified. axe's `list` and `listitem` rules are turned off in `nav-list.stories.ts`, since axe can't follow the slot.
- [ ] The list carries no `menu`, `menubar` or `tree` role, so its links and buttons keep their own roles. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [x] `appearance` changes the layout and nothing else: a `tabs` strip is still a `<ul>` of list items holding links, with no `tab` or `tablist` role anywhere. Per WCAG 4.1.2 (Name, Role, Value). Verified by `keeps its list semantics as a tab strip` in `nav-list.test.ts`.

### Visual

- [x] In a `tabs` strip, the current page is marked by a bar along the strip's rule and by a heavier label weight, so the difference is shape and weight as well as color. The bar reaches 3:1 against the surface in the light and dark themes, and no other item has one. Per WCAG 1.4.1 (Use of Color) and 1.4.11 (Non-text Contrast). Verified by `marks the current page in a tab strip with a bar at 3:1, and only that page` and `marks the current page in a tab strip by weight as well as color` in `nav-item.browser.test.ts`.
- [ ] That bar stays visible in forced colors mode, using the `Highlight` system color, which an ordinary color would be forced to the canvas color and lose. Not yet verified, since the browser tests can't emulate forced colors.

### Keyboard and focus

- [ ] The list adds no keyboard handling of its own: Tab and Shift+Tab move through the items' links and buttons in DOM order. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Not yet verified.

## Depends on usage

- 1.3.1 (Info and Relationships): wrap the top-level list in a named `<nav>` landmark. See [Accessibility](./NavList.mdx#accessibility).
- 1.3.1 (Info and Relationships): slot only `craft-nav-item`s into the list. See [Accessibility](./NavList.mdx#accessibility).
