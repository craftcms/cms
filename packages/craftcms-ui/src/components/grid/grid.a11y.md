# `craft-grid` accessibility

Follows the WAI-ARIA APG [Layout Grid](https://www.w3.org/WAI/ARIA/apg/patterns/grid/examples/layout-grids/) examples: rows of links, buttons and menu buttons, arranged in `craft-grid-row`s and `craft-grid-cell`s. The grid is one Tab stop, and the arrow keys move between its cells. Each cell holds one interactive element, which takes focus. A cell with none takes focus itself.

A grid is a less common pattern. Use it only for a long, self-contained list, such as search results or a palette of commands, where at least some rows hold more than one control, such as an item and a menu of its own actions. Use it only where there's room to show keyboard instructions beside it. Few web UIs use a grid's keys, so without instructions people won't know them, and the grid confuses more than it helps.

**Not for:**

- A list of links or buttons with one control per item. Use a plain list, which Tab moves through in order.
- Site or section navigation. Use `craft-nav-list`.
- Static tabular data with column headers. Use a `<table>`.
- Picking a value from a list. Use `craft-select-rich` or `craft-combobox`.
- A button that shows a list of actions. Use `craft-action-menu`.
- Content that's edited in place in its cells, such as a spreadsheet. The APG Data Grid's edit mode isn't supported.

A change that needs any of these is a different pattern. Don't add a mode to `craft-grid` for it.

## Requirements

### Semantics

- [x] The host carries `role="grid"`, and each `craft-grid-row` and `craft-grid-cell` carries `role="row"` and `role="gridcell"`, so cells are owned by rows and rows by the grid. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `exposes grid, row and gridcell roles` in `grid.test.ts`.
- [x] The grid is named by `label`, applied to the host as `aria-label`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `names the grid from label` in `grid.test.ts`.
- [ ] The grid can be named by a visible heading through `aria-labelledby` on the host, since `role="grid"` is on the host itself. Per WCAG 1.3.1 (Info and Relationships) and 2.5.3 (Label in Name). Not yet verified with a screen reader.
- [x] Rows and cells inside a nested `craft-grid` belong to that grid, not to the outer one. Per WCAG 1.3.1 (Info and Relationships). Verified by `leaves a nested grid’s rows to the nested grid` in `grid.test.ts`.

### Keyboard and focus

- [x] Exactly one cell is in the Tab order: the first cell of the first row, until another cell takes focus, which then becomes the grid's Tab stop. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Verified by `keeps one tab stop, on the first cell until another takes focus` in `grid.test.ts` and `is one tab stop, which returns to the last focused cell` in `grid.browser.test.ts`.
- [x] Focus goes to the cell's interactive element, or to the cell itself when it has none. Controls inside a cell's menu or other popup aren't counted. Per WCAG 2.1.1 (Keyboard). Verified by `focuses a cell with no interactive element itself` and `leaves controls inside a cell’s popup out` in `grid.test.ts`, and `moves between a link and a menu button with the arrow keys` in `grid.browser.test.ts`.
- [x] Right and Left move to the next or previous cell in the row, and stop at the row's ends. They're mirrored in a right-to-left layout. Per WCAG 2.1.1 (Keyboard). Verified by `moves along a row with Right and Left, stopping at the ends` in `grid.test.ts`, and `mirrors Right and Left when the layout is right to left` in `grid.browser.test.ts`.
- [x] Down and Up move to the same column in the next or previous row, or to that row's last cell when it's shorter, and stop at the first and last rows. Per WCAG 2.1.1 (Keyboard). Verified by `moves between rows with Down and Up, keeping the column where it can` in `grid.test.ts`.
- [x] Home and End move to the first and last cells in the row. Control+Home and Control+End (or Command on macOS) move to the first cell of the first row and the last cell of the last row. Per WCAG 2.1.1 (Keyboard). Verified by `jumps to the ends of the row with Home and End, and of the grid with Control` in `grid.test.ts`.
- [x] Page Down and Page Up move five rows down or up, stopping at the first and last rows. Per WCAG 2.1.1 (Keyboard). Verified by `moves five rows with Page Down and Page Up, stopping at the ends` in `grid.test.ts`.
- [x] Hidden rows and cells, and disabled controls, are skipped. Per WCAG 2.1.1 (Keyboard). Verified by `skips hidden rows, hidden cells and disabled controls` in `grid.test.ts`.
- [x] The Tab stop survives changes to the rows: when the cell holding it is removed, it moves to the same position in what's left, or to the first cell. Per WCAG 2.4.3 (Focus Order). Verified by `keeps the tab stop in place when the row holding it is removed` in `grid.test.ts`.
- [x] `resetTabStop()` puts the Tab stop back on the first cell, so rows replaced by different ones, such as new search results, start from the top. Per WCAG 2.4.3 (Focus Order). Verified by `puts the tab stop back on the first cell when asked to` in `grid.test.ts`.
- [x] A control that resets its own `tabindex`, such as a `craft-button` whose `disabled` changes, is kept out of the Tab order unless it holds the grid's Tab stop. Per WCAG 2.4.3 (Focus Order). Verified by `takes a control that resets its own tabindex back out of the tab order` in `grid.test.ts` and `keeps a craft-button out of the tab order when it resets its own tabindex` in `grid.browser.test.ts`.
- [x] Keys pressed inside an open menu in a cell, or in a text field, are left to that control. Per WCAG 2.1.1 (Keyboard) and 2.1.2 (No Keyboard Trap). Verified by `leaves keys in a text field to the field` and `leaves keys pressed inside a cell’s popup to the popup` in `grid.test.ts`, and `leaves arrow keys in an open menu to the menu` in `grid.browser.test.ts`.
- [x] Removing the grid from the page gives every control back the `tabindex` it had before. Per WCAG 2.4.3 (Focus Order). Verified by `gives controls back their own tabindex when it is removed` in `grid.test.ts`.
- [ ] Known gap: a `craft-button` with `href` renders its link inside its shadow root and has no `tabindex` of its own to manage, so it can't be a cell's interactive element. Use a plain `<a>` in a grid cell.

## Depends on usage

- 2.4.7 (Focus Visible): the grid draws no focus ring of its own. Each cell's control shows its own. See [Accessibility](./Grid.mdx#accessibility).
- 1.3.1 (Info and Relationships): name each group of items with its own heading and grid, rather than making the groups rows of one grid. See [Accessibility](./Grid.mdx#accessibility).
- 1.3.1 (Info and Relationships): put one interactive element in each cell. A second one isn't reachable with the arrow keys. See [Accessibility](./Grid.mdx#accessibility).
- Keyboard instructions: show the grid's keys beside it, in a region named by a heading, as a list with one item per command. Don't connect them to the grid with `aria-describedby`. See [Accessibility](./Grid.mdx#accessibility).
- 4.1.3 (Status Messages): when the rows come from a search, announce how many there are in a status message outside the grid. See [Accessibility](./Grid.mdx#accessibility).
