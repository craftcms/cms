# `craft-nav-item` accessibility

Used inside `craft-nav-list`; see [`nav-list.a11y.md`](../nav-list/nav-list.a11y.md) for the pattern.

## Requirements

### Semantics

- [ ] The host carries `role="listitem"` and its inner `<li>` is `role="presentation"`, so the item belongs to its `craft-nav-list`'s `<ul>` across shadow roots, nested lists included. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.
- [x] An item with `href` renders a link, and an item without one that opens a flyout or has `button` set renders a `<button type="button">`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `focuses the row, whichever element it is rendered as`, `makes a hrefless item with a flyout a button, so it can be reached` and `renders its label as a button that reaches the item’s click listener` in `nav-item.test.ts`.
- [x] An item with no `href`, no flyout and no `button` renders as plain text, not a control. Per WCAG 4.1.2 (Name, Role, Value). Verified by `leaves a hrefless item with nothing to disclose as a label` and `stays a label without it` in `nav-item.test.ts`.
- [x] A link carries `aria-current="page"` when `current`, `aria-current="true"` when only `active` (an ancestor of the current page), and `aria-current="false"` otherwise. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `announces only the page itself as the page` in `nav-item.test.ts`.
- [ ] An item rendered as a button carries `aria-current="true"` on the native `<button>` when `current`, never `page`. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [x] Collapsed (`icon-only`), the row's link or button is named by `aria-label` with the item's label text, since the label moves into a tooltip or the flyout. The name follows the label when its text changes. Per WCAG 4.1.2 (Name, Role, Value) and 2.5.3 (Label in Name). Verified by `names a rail item, whose label is projected away from it` and `is a named button with a tooltip when collapsed` in `nav-item.test.ts`, and `renames a rail item whose label text is edited in place` in `nav-item.browser.test.ts`.
- [x] Collapsed, the first-letter stand-in for a missing icon is hidden from assistive technology, so the name isn't read twice. Per WCAG 1.1.1 (Non-text Content). Verified by `stands the first letter in for a missing icon when collapsed` in `nav-item.test.ts`.
- [x] Collapsed, a `group` heading becomes an `<hr>` that keeps the heading's text as its `aria-label`. Per WCAG 1.3.1 (Info and Relationships). Verified by `turns a collapsed heading into the rule between the runs it separates` in `nav-item.test.ts`.
- [ ] A heading over a subnav (a `group` item, or an item whose label is styled text) is exposed as a heading, or names the nested list. Per WCAG 1.3.1 (Info and Relationships). Known gap: the label renders as a plain `<span>`, so the grouping is visual only and the heading isn't announced as one.
- [ ] With `external`, the link opens in a new tab and its external-link icon carries a text alternative. Per WCAG 1.1.1 (Non-text Content). Known gap: `external` is declared but never rendered, so it sets no `target` and shows no icon.

### Subnav disclosure

- [x] The subnav toggle carries `aria-expanded`, which follows the subnav as it opens and closes. Per WCAG 4.1.2 (Name, Role, Value). Verified on the rail by `lets you collapse the branch you are in without expanding the nav` in `nav-item.test.ts`.
- [x] The rail's subnav toggle is named by an element that is actually rendered, so the button isn't left unnamed. Per WCAG 4.1.2 (Name, Role, Value). Verified by `names the rail toggle by something that is actually rendered` in `nav-item.test.ts`.
- [ ] Each subnav toggle's name says which branch it belongs to. Per WCAG 2.4.6 (Headings and Labels). Known gap: on the rail, every toggle is named only "Toggle subnavigation".
- [x] The flyout toggle carries `aria-expanded`, `aria-controls` pointing at the flyout, and an `aria-label` naming its branch. Per WCAG 4.1.2 (Name, Role, Value). Verified by `opens the flyout from its own disclosure instead` in `nav-item.test.ts`.
- [x] A row with a flyout carries `aria-expanded` and `aria-controls` on its own link or button, not on the positioning anchor, and a childless row carries neither. Per WCAG 4.1.2 (Name, Role, Value). Verified by `tells a screen reader that a labeled item discloses a flyout`, `points the rail item at the flyout it expands`, `keeps the anchor out of the flyout aria, which it cannot carry` and `leaves a childless item without a disclosure to announce` in `nav-item.test.ts`.
- [ ] Only a button carries `aria-expanded`, as in the Disclosure Navigation Menu example. Per WCAG 4.1.2 (Name, Role, Value). Known deviation: a link row with a flyout also carries it, although activating the link navigates rather than toggling the flyout.
- [ ] The flyout panel isn't announced as an unnamed dialog. Per WCAG 4.1.2 (Name, Role, Value). Known gap: `craft-popover` gives its panel `role="dialog"`, and the item sets no `label` on it.

### Keyboard and focus

- [x] Focus landing on an item doesn't open its flyout, so tabbing past a branch doesn't tab through its children. Per WCAG 2.4.3 (Focus Order). Verified by `leaves the flyout shut when focus lands on the item` in `nav-item.test.ts`.
- [x] Enter on the flyout toggle opens the flyout and Space closes it, and neither press reaches the row's link. Per WCAG 2.1.1 (Keyboard). Verified by `opens the flyout from its chevron instead of following the link` in `nav-item.browser.test.ts`.
- [x] On the rail, the flyout toggle stays in the Tab order while hidden, and shows by opacity once focused. Per WCAG 2.1.1 (Keyboard) and 2.4.7 (Focus Visible). Verified by `keeps a way into a collapsed flyout that isn’t the pointer` in `nav-item.test.ts`.
- [x] The flyout toggle comes before the flyout in the DOM, so Tab reaches the way in before the contents. Per WCAG 2.4.3 (Focus Order). Verified by `puts the disclosure ahead of what it opens` in `nav-item.test.ts`.
- [x] The flyout stays open while focus moves within the item and its subnav, and closes once focus leaves both. Per WCAG 2.4.3 (Focus Order). Verified by `stays open while focus moves within it` and `closes once focus actually leaves` in `nav-item.test.ts`.
- [ ] Escape closes an open flyout and returns focus to its toggle. Per WCAG 2.1.1 (Keyboard) and 1.4.13 (Content on Hover or Focus). Not yet verified. `follows the overlay when it closes itself` calls `hide()` rather than pressing Escape.
- [x] Enter and Space on a control in the `actions` slot don't reach the row's link, while other keys such as Escape still bubble. Per WCAG 2.1.1 (Keyboard). Verified by `keeps a lent control's keyboard activation from reaching the row` in `nav-item.browser.test.ts`.
- [x] `focus()` moves focus to the row's link or button, whichever element the item is currently rendered as. Per WCAG 2.4.3 (Focus Order). Verified by `focuses the row, whichever element it is rendered as` in `nav-item.test.ts`.
- [ ] A button item keeps focus when `current` or `active` changes on it. Per WCAG 2.4.3 (Focus Order). Not yet verified.
- [x] A focused rail row draws a 2px ring, the same as its chevron's. Per WCAG 2.4.7 (Focus Visible). Verified by `draws the same focus ring on the row and on its chevron` in `nav-item.browser.test.ts`. The ring's contrast isn't checked.

### Visual

- [x] A flyout opened by hover stays open while the pointer travels into it, including across a neighboring row, and closes only after a delay. Per WCAG 1.4.13 (Content on Hover or Focus). Verified by `stays open across the gap, then closes after the delay` and `cancels a pending close when the pointer comes back` in `nav-item.test.ts`, and `keeps a flyout open while the pointer cuts across a neighbor` in `nav-item.browser.test.ts`.
- [ ] The current page and the items on the trail to it are marked by more than color, at 3:1 against the surface in the light and dark themes. Per WCAG 1.4.1 (Use of Color) and 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] In forced colors mode, the current item, the trail bar, and the focus ring stay visible. Not yet verified. The trail bar is drawn with `background-color`, which forced colors removes.
- [ ] Every row and toggle is at least 24×24 CSS px, or has room around it. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified.

### Reflow and zoom

- [x] A flyout is capped to the room left below it and scrolls inside the popover's single scroller, rather than running off the screen. Per WCAG 1.4.10 (Reflow). Verified by `caps the flyout to the room left below it` and `leaves the flyout with exactly one scroller` in `nav-item.test.ts`.
- [ ] Long labels wrap rather than being truncated, at 320px wide and 400% zoom. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Not yet verified.

## Depends on usage

- 4.1.2 (Name, Role, Value): give every item label text, including collapsed ones. See [Accessibility](./NavItem.mdx#accessibility).
- 2.4.4 (Link Purpose (In Context)): use `href` for anything that goes somewhere, and `button` only for actions. See [Accessibility](./NavItem.mdx#accessibility).
- 1.3.1 (Info and Relationships): set `current` and `active` to match the page. See [Accessibility](./NavItem.mdx#accessibility).
