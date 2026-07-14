# Grouped Entry Type Manager

A TypeScript port of the legacy jQuery `Craft.GroupedEntryTypeManager` (+ its
`Group` inner class) and the cross-group behaviors of
`Craft.GroupedEntryTypeSelectInput` onto the modern **`@craftcms/garnish`**
`Base`. It powers the Matrix field settings' grouped entry type UI
(`_components/fieldtypes/Matrix/settings.twig`), orchestrating the self-booting
[`<craft-component-select>`](../component-select/component-select.ts) elements
that replaced the legacy `Craft.ComponentSelectInput` chain.

## What changed

- **Class system.** `Garnish.Base.extend({…})` → `class extends Base`; `init()`
  is still the setup method but is invoked from a `constructor` via the
  `new.target` leaf guard (the same construction contract as the
  SortableCheckboxSelect / EditableTable ports);
  `Craft.GroupedEntryTypeManager.Group` → the exported `Group` class.
- **Select orchestration, not subclassing.** The legacy
  `GroupedEntryTypeSelectInput` subclassed the jQuery select input to propagate
  option visibility, inject chip actions, and stamp `{id, group}` input values.
  Those now ride `craft-component-select`'s public seams: the bubbling `change`
  event (cross-group option sync in `refresh()`), the bubbling
  `define-chip-actions` event ("Move to previous/next group" items), the
  `getInputValue` hook (group-aware chip input JSON), `adoptChip()` (menu
  moves between groups), and `releaseSort()` (each select hands its chip
  `DragSort` to the manager's cross-group one — see below).
- **Titlebar controls.** The legacy jQuery menubtn + `.move` handle →
  a data-driven `craft-action-menu` (Rename / Remove) plus a horizontal
  `<craft-reorder-button>` that is both the group's `DragSort` handle and its
  Move forward/backward menu (replacing the legacy menu's move items).
- **Cross-group chip drag.** One manager-level `DragSort` (`initChipSort` /
  `syncChipSort`) spans every group's `ul.components` so a chip can be *dragged*
  between groups — a per-select sorter can't drop into a sibling's list. Each
  child select `releaseSort()`s its own chip sorter (legacy
  `GroupedEntryTypeSelectInput.initComponentSort` no-op'd for the same reason);
  the manager registers every chip `li` plus a per-group
  `entry-type-group--caboose` sentinel `li` (the end-of-list / empty-group drop
  target, gated out of `canInsertAfter`). On drop, `refresh()` rewrites the
  moved chip's `{group}` JSON (`Group.refresh`) and re-syncs. On touch there's
  no `DragSort` at all — the reorder menu's "Move to previous/next group" items
  handle it.
- **`.data()` → WeakMap.** `$container.data('entryTypeManager')` /
  `.data('entryTypeGroup')` → module-level WeakMaps
  (`support.ts`'s `groupedEntryTypeManagerData`, and an internal `groupData`).
  The chip/titlebar handlers resolve the live instances through them at event
  time, so a destroy/re-boot (ControllerElement teardown) takes over cleanly.
- **No JS blob for new groups.** The legacy `entryTypeSelectHtml` +
  `entryTypeSelectJs` pair → a `<template data-entry-type-select>` cloned
  client-side (TEMP_ID swap); the select inside self-boots.
- **ESM/bundle wiring.** Imported from `resources/js/cp.ts`; the legacy
  `GroupedEntryTypeManager.js` and `GroupedEntryTypeSelectInput.js` are removed
  from the legacy `Craft.js` bundle.

## What deliberately stays jQuery / legacy seams

`Craft.t`, `Craft.ui.icon`, `Craft.namespaceId` / `namespaceInputName`,
`Craft.hasMousePointerEvents`, `Craft.sendActionRequest`, and the
jQuery-returning `Craft.ui.createSortableCheckboxSelect` (the Default Table
Columns rebuild) — accessed via the page globals (`declare const Craft/$`).

## Construction & global

`window.Craft.GroupedEntryTypeManager` is assigned the **plain modern ES
class** (plus `.Group`) — **no `compatify`**, because nothing subclasses it via
the legacy `Garnish.Base.extend()`. The legacy settings keys are still
accepted (`$defaultColumnsContainer` unwraps; `entryTypeSelectJs` is ignored).
`Craft.GroupedEntryTypeSelectInput` is gone without replacement — its behavior
lives in the manager + `craft-component-select` now.

The `<craft-entry-type-manager>` custom element self-boots the class around the
markup it wraps (attributes: `namespace`, `default-columns-id`, `disabled` for
read-only inertness) and attaches the chip move-action listener ahead of its
deferred boot, so chips wired by earlier-booting selects still get their items.

## Files

- `grouped-entry-type-manager.ts` — the `GroupedEntryTypeManager` and `Group`
  classes, plus `attachChipMoveActions`.
- `grouped-entry-type-manager.ce.ts` — `<craft-entry-type-manager>`, the
  self-booting custom element (a `ControllerElement`).
- `support.ts` — the `.data()`-replacement WeakMap.
- `index.ts` — assigns `window.Craft.GroupedEntryTypeManager` (+ `.Group`) and
  registers `<craft-entry-type-manager>`. Imported from `resources/js/cp.ts`.
- `components/` — unrelated Vue components (Inertia section settings), imported
  directly by path.

## Deferred

- Behavioral/browser verification (add/rename/remove/reorder groups, chip
  moves — menu *and* cross-group drag, option sync, default-columns rebuild) is
  left to manual testing.
