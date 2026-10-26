# `craft-grid-row` accessibility

A row of a `craft-grid`, following the WAI-ARIA APG [Layout Grid](https://www.w3.org/WAI/ARIA/apg/patterns/grid/examples/layout-grids/) examples. The grid handles keyboard navigation; see `grid.a11y.md`.

**Not for:**

- A row of a `<table>`. Use `<tr>`.
- Use outside a `craft-grid`.

## Requirements

### Semantics

- [x] The host carries `role="row"` unless it's given another role. Per WCAG 1.3.1 (Info and Relationships). Verified by `exposes grid, row and gridcell roles` and `keeps a role given to a row or cell, and slots their content` in `grid.test.ts`.
- [x] The row's cells are slotted, so the `craft-grid-cell`s are exposed as owned by the row. Per WCAG 1.3.1 (Info and Relationships). Verified by `keeps a role given to a row or cell, and slots their content` in `grid.test.ts`.
