# BaseElementIndex — shared index shell for table & card views

**Date:** 2026-07-01
**Branch:** `feature/inertia-element-indexes`
**Status:** Approved design, pending implementation plan

## Problem

`AdminTable.vue` and `ElementCards.vue` render the same element index two ways (a
table vs a card grid) and duplicate a large amount of code between them:

- ~11 identical props (`table`, `selectable`, `loading`, `from/to/total`,
  `enableAdjustPageSize`, `pageSizeOptions`, `actions`, `elementType`, `source`,
  `context`)
- Selection state + handlers (select-all guard, per-item toggle, `selectedIds`,
  bulk-action visibility)
- Footer/pagination proxies + `show*` flags
- The entire ~80-line footer template (bulk-actions bar + "X–Y of Z" + pager +
  page-size select) — byte-for-byte identical
- The shell chrome (`cp-table-wrapper > cp-table-header + cp-table-body +
  cp-table-footer`) and its CSS, split across `base.css` and both components'
  scoped blocks

The goal is to abstract the shared shell/footer/selection into a single
`BaseElementIndex` component (plus composables), rename the shared **shell**
classes to `element-index-*`, and fold in a few missing Craft 5 features at the
shell/selection layer.

## Decisions (resolved during brainstorming)

1. **Rename scope: shell classes only.** Rename
   `cp-table-wrapper/header/body/footer` → `element-index__*`. Leave the generic
   `.cp-table` / `.cp-table-cell` / `.cp-table--*` table primitive untouched — it
   is a general `<table>` style used by legacy Twig (`editableTable.twig`,
   `PhpInfo.twig`) and Storybook, and the card view has no cells.
2. **Structure: page composes the base.** `content/Index.vue` renders
   `<BaseElementIndex>` directly, with either a table or the card grid in its body
   slot.
3. **AdminTable fate: split (bare core + base wraps it).** Extract the bare
   `<table>` into a new `DataTable.vue`. `BaseElementIndex` owns shell + footer +
   selection. A thin `AdminTable.vue` (= `BaseElementIndex` + `DataTable`) keeps
   the ~19 standalone callers' public API unchanged.
4. **Craft 5 features to include now:** ARIA live region (shell), shift-click
   range selection, keyboard selection navigation. **Deferred:** infinite scroll /
   load-more (its own project); body-specific features (structure/hierarchy,
   drag-sort beyond current reorder, inline editing, thumbnails, sticky proxy
   scrollbar) are out of scope.

## Architecture

```
content/Index.vue  ──renders──►  BaseElementIndex        (shell + footer + aria-live)
                                    ├─ #header slot ► ElementIndexToolbar
                                    └─ #body slot   ► DataTable    (bare <table>)    ← table mode
                                                      ElementCards (bare card grid)  ← cards mode

AdminTable.vue (thin wrapper, unchanged public API for the ~19 callers)
   └─ renders BaseElementIndex + DataTable internally
```

### `BaseElementIndex.vue` (new — `resources/js/modules/elements/components/`)

Owns everything shared between the two views:

- **Shell chrome:** `.element-index` wrapper → `.element-index__header` (slot
  `header`) + `.element-index__body` (slot `body`, with `aria-busy` while loading)
  + `.element-index__footer`.
- **Footer:** the full footer template — bulk-actions bar
  (`showBulkActions && hasSelection`), displayed-rows text (`from/to/total`),
  pager, and page-size select. The footer pagination UI proxies (`pageIndexProxy`,
  `pageSizeProxy`, `showPagination`, `showPageSize`, `showDisplayedRows`,
  `showFooter`) live here directly — this is now their only consumer, so no
  separate composable is needed for them.
- **ARIA live region:** a visually-hidden `role="status" aria-live="polite"`
  element announcing what the shell owns — loading state, result totals on load,
  and selection changes ("3 selected" / "Selection cleared").
- Uses `useElementIndexSelection` for footer bulk-action visibility and the
  selection announcements.

Props (shared set): `table`, `selectable`, `readOnly`, `loading`, `from`, `to`,
`total`, `enableAdjustPageSize`, `pageSizeOptions`, `actions`, `elementType`,
`source`, `context`.
Slots: `header`, `body`.
Emits: `action-performed`.

Placement in `modules/elements` matches the existing dependency direction —
`AdminTable` already imports `BulkActionsBar` from `modules/elements`.

### `DataTable.vue` (new — the bare table core)

Extracted from today's `AdminTable.vue`: just the `<table>` — thead/tbody, column
rendering, reorder handles, loading skeleton, spacing/compact variants, and the
`caption` (fed by `title`). Body-only; renders no shell or footer. Used both
inside the `AdminTable` wrapper and directly in `content/Index.vue`'s body slot.

Wires its checkboxes via `useElementIndexSelection`. Owns its own focus /
`tabindex` / keydown handling for keyboard selection nav (table-row specific),
calling the composable's selection primitives.

### `ElementCards.vue` (modified → bare)

Keeps the card grid (`card-grid`, `card-grid-header`) and per-card checkbox; loses
the shell and footer (now provided by `BaseElementIndex`). It is only used on the
content index today, so there is no external fallout. Owns its own card-grid focus
/ keydown handling for keyboard selection nav.

### `AdminTable.vue` (modified → thin wrapper)

Renders `BaseElementIndex` + `DataTable`, splitting `$props` into a `baseProps`
subset (shared/footer/selection) and a `viewProps` subset (table-core:
`reorderable`, `layout`, `spacing`, `title`), forwarding `@reorder` and
`@action-performed`. `loading` goes to both (base for `aria-busy`/live region,
`DataTable` for the skeleton). The ~19 standalone callers keep their current API.

### `useElementIndexSelection.ts` (new composable — `modules/elements/composables/`)

`(table, {readOnly, actions})` → `selectedIds`, `hasSelection`, `hasBulkActions`,
`showBulkActions`, `bulkActionsActive`, `clearSelection`, `readOnly` (resolved via
the `usePage` fallback), plus the selection handlers used by both bodies:

- `onToggleAllSelected(event)` — guarded select-all (existing behavior).
- `onRowSelectionClick(row, {shiftKey})` — **new shift-click range selection.**
  With `shiftKey` and a stored anchor, selects the range between the anchor and the
  clicked row in current row-model order (`table.getRowModel().rows`); otherwise
  toggles the row and re-anchors. Owns the `anchorIndex` ref and the
  `craft-checkbox` `model-value-changed` guard (only acts when the event value
  differs from current state).
- `toggleRow(row)` / `extendSelectionTo(row)` — primitives for the bodies'
  **keyboard nav** (arrow to move focus, space to toggle, shift+arrow to extend).
  Focus, `tabindex`, and keydown wiring stay in the body components (table rows vs
  cards differ structurally); the composable performs the selection math only.

Called independently by `BaseElementIndex`, `DataTable`, and `ElementCards` — all
derive from the same shared `table` instance, so no prop threading is required.

**Implementation note (for planning):** `craft-checkbox`'s `model-value-changed`
event does not carry `shiftKey`. Reading modifier state for range selection will
require a native `click`/`keydown` handler on the checkbox or row that captures
`event.shiftKey` and passes it explicitly to `onRowSelectionClick`. The composable
API takes an explicit `{shiftKey}` rather than digging it out of the event.

## Shell class rename map (shell-only)

| Old | New |
|---|---|
| `cp-table-wrapper` | `element-index` |
| `cp-table-header` | `element-index__header` |
| `cp-table-body` | `element-index__body` |
| `cp-table-body__header` | `element-index__body-header` |
| `cp-table-footer` | `element-index__footer` |
| `cp-table-footer--has-selection` | `element-index__footer--has-selection` |
| `cp-table-footer__lead` | `element-index__footer-lead` |

Shell CSS consolidates into `BaseElementIndex.vue`'s scoped styles and is removed
from `packages/craftcms-cp/src/styles/shared/base.css` and from the old scoped
blocks in `AdminTable.vue` / `ElementCards.vue`.

## Files

**New**
- `resources/js/modules/elements/components/BaseElementIndex.vue`
- `resources/js/modules/elements/components/DataTable.vue`
- `resources/js/modules/elements/composables/useElementIndexSelection.ts`

**Modified**
- `resources/js/modules/admin-table/components/AdminTable.vue` → thin wrapper
- `resources/js/modules/elements/components/ElementCards.vue` → bare card grid
- `resources/js/pages/content/Index.vue` → composes `BaseElementIndex`; `#table-header` → `#header`
- `packages/craftcms-cp/src/styles/shared/base.css` → remove shell rules

**Untouched**
- Existing `useElementIndex*` composables (`Pagination` = TanStack model config,
  `Sort`, `Filters`, `Columns`, `ViewMode`, `ViewState`, `Loading`)
- The `.cp-table` / `.cp-table-cell` / `.cp-table--*` primitive and its legacy Twig
  consumers
- The ~19 standalone `AdminTable` callers (API preserved by the wrapper)

## Testing

- **Unit — `useElementIndexSelection`** (against a mock TanStack table): per-row
  toggle, select-all guard (ignores programmatic `model-value-changed`), shift-click
  range-selection math (anchor + range in row-model order), and the
  `showBulkActions` / `bulkActionsActive` flags.
- **Component smoke:** `BaseElementIndex` (footer shows/hides, bulk bar appears on
  selection, aria-live region present and updates), `DataTable`, `ElementCards`,
  plus a couple of the standalone `AdminTable` callers to confirm no regression.
- Confirm the exact Vitest config path for `resources/js` during planning.

## Out of scope / deferred

- Infinite scroll / load-more footer mode (separate project).
- Body-specific Craft 5 features: structure/hierarchy view, drag-sort beyond the
  current reorder, inline editing, thumbnails, sticky horizontal proxy scrollbar.
- Renaming the `.cp-table` cell primitive or the slot beyond `#table-header` →
  `#header`.
