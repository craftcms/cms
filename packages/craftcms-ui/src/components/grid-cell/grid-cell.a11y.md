# `craft-grid-cell` accessibility

A cell of a `craft-grid-row`, following the WAI-ARIA APG [Layout Grid](https://www.w3.org/WAI/ARIA/apg/patterns/grid/examples/layout-grids/) examples. The grid decides which element in the cell takes focus; see `grid.a11y.md`.

**Not for:**

- A cell of a `<table>`. Use `<td>` or `<th>`.
- Use outside a `craft-grid-row`.

## Requirements

### Semantics

- [x] The host carries `role="gridcell"` unless it's given another role. Per WCAG 1.3.1 (Info and Relationships). Verified by `exposes grid, row and gridcell roles` and `keeps a role given to a row or cell, and slots their content` in `grid.test.ts`.
- [x] The cell's content is slotted, so the cell's text and controls are exposed as its content. Per WCAG 1.3.1 (Info and Relationships). Verified by `keeps a role given to a row or cell, and slots their content` in `grid.test.ts`.
