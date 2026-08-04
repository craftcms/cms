# Component Select

A TypeScript port of the legacy jQuery `Craft.ComponentSelectInput`
(`packages/craftcms-legacy/cp/src/js/ComponentSelectInput.js`) onto the modern
**`@craftcms/garnish`** `Base`, exposed as the self-booting
`<craft-component-select>` custom element. It powers
`_includes/forms/componentSelect.twig` and is orchestrated by
[`<craft-entry-type-manager>`](../grouped-entry-type-manager/grouped-entry-type-manager.ts)
for the Matrix field settings' grouped entry type UI.

## The element-as-API-facade split

Unlike a plain garnish port, this module splits into two files the way
`grouped-entry-type-manager` and `sortable-checkbox-select` do:

- **`component-select.ts`** — `ComponentSelect`, the `Base` subclass that owns
  all the behavior: chip wiring, drag-sort, the Choose menu, the Create-button
  slideout, and the `change` event.
- **`component-select.ce.ts`** — `CraftComponentSelect`, the
  `<craft-component-select>` custom element (a `ControllerElement`). It parses
  attributes into `ComponentSelectSettings` and boots a `ComponentSelect`
  around the element's light-DOM children.

The twist here, relative to the other two ports: **the element stays the
public API**, not the controller. `<craft-entry-type-manager>`'s `Group` class
(and any other consumer) calls `.chips`, `.selectedIds`, `.addComponent()`,
`.adoptChip()`, `.releaseSort()`, `.showOption()`/`.hideOption()`,
`.openChooseMenu()`, and sets
`.getInputValue` directly on the `<craft-component-select>` DOM element — the
same surface the pre-split single-file version exposed. `component-select.ce.ts`
re-exposes each of those as a forwarding member: the getters
(`initialized`/`selectedIds`/`chips`) fall back to `false`/`[]` before boot,
and the action methods (`addComponent`, `removeComponent`, `showOption`,
`hideOption`, `openChooseMenu`, `adoptChip`, `releaseSort`, `moveComponent`)
no-op via `this.instance?.method(...)` until `ControllerElement`'s retrying
boot resolves the controller.

`releaseSort()` is the seam the grouped entry type manager uses for cross-group
chip drag: it destroys this select's own chip `DragSort` (and blocks its
recreation) so one manager-level sorter can span every group's list — a
per-select sorter can't drop into a sibling's list. The chips'
`<craft-reorder-button>`s stay (they're the outer sorter's handles and still
drive the touch move menu); the manager owns registering the chip `li`s with
its sorter. Legacy `GroupedEntryTypeSelectInput.initComponentSort` no-op'd for
the same reason.

Why keep the element as the facade instead of exporting the controller as the
API and having consumers reach through it? Two of the seams are
boot-order–sensitive in ways a controller reference can't paper over:

- **`getInputValue`.** `Group.init()` (in `grouped-entry-type-manager.ts`)
  assigns `select.getInputValue = (id) => ...` immediately after querying
  `container.querySelector('craft-component-select')` — which can run before
  the select's own `ControllerElement` boot (a `requestAnimationFrame` retry)
  has resolved `:scope > ul` and constructed the controller. If the hook lived
  on the controller, that assignment would either throw (no instance yet) or
  silently target an instance that gets replaced. Instead the element owns a
  private `#getInputValue` field with a public accessor pair — boot-independent,
  settable at any time — and `create()` passes the controller a **settings
  callback**, `getInputValue: () => this.#getInputValue`, instead of a
  snapshotted value. Every read inside `ComponentSelect` goes through
  `this.settings.getInputValue?.()?.(id)`, so it always sees whatever the
  element's field currently holds, including a value set after boot.
- **`define-chip-actions`.** This event's `detail.actions` array is
  synchronously mutated by ancestor listeners (`<craft-entry-type-manager>`
  pushes its "Move to previous/next group" items into it) — a garnish
  `trigger()` round-trip through the pub/sub emitter wouldn't preserve that
  synchronous, single-array mutation contract the way a native
  `dispatchEvent` does. So it stays a native, bubbling `CustomEvent` dispatched
  directly on `container` (the element) from `ComponentSelect`, not routed
  through `Base.trigger`.

## Events

- **`change`** — `ComponentSelect` emits it via garnish `this.trigger('change')`
  whenever membership or order changes (add, remove, drag-sort, or a
  reorder-menu move), suppressed until boot completes. `ControllerElement`'s
  event bridge (`#bridgeEvents`, listening on the controller's `'*'`) re-emits
  every garnish `trigger()` as a bubbling DOM `CustomEvent` of the same name on
  `<craft-component-select>` — that's what `<craft-entry-type-manager>` listens
  for (`container.addEventListener('change', ...)`) to resync cross-group
  state.
- **`define-chip-actions`** — dispatched natively (not via the bridge) on
  `container` while a chip gets its one-time wiring; see above for why.

## No legacy-global shim

The other ported modules (`GroupedEntryTypeManager`, `SortableCheckboxSelect`)
assign their class onto `window.Craft.*` so old `new Craft.Whatever(...)`
call sites keep working. This module doesn't, on purpose: providing
`window.Craft.ComponentSelect` would just be a second, incompatible
implementation sitting next to `Craft.ComponentSelectInput`. Migrated surfaces
render `<craft-component-select>` instead of calling
`new Craft.ComponentSelectInput(...)`, and the element (not this module's
`ComponentSelect` controller) is the API they're written against. The module's
"shim," such as it is, is that deliberate absence.

Core no longer instantiates `Craft.ComponentSelectInput` — every core CP
surface renders `<craft-component-select>`. The legacy class no longer ships in
the main craftcms-legacy CP bundle either; it was relocated verbatim to
`packages/craftcms-legacy/cpcompat/src/component-select-input.js` (a real
implementation, not a warn stub) purely so the `componentSelect.twig` `jsClass`
escape hatch keeps booting plugin subclasses. See that file and `CpCompatAsset`.

## What deliberately stays jQuery / legacy seams

`Craft.t`, `Craft.ui.icon`, `Craft.addActionsToChip` (with its jQuery
`disclosureMenu()` fallback for old/plugin-rendered chip markup),
`Craft.sendActionRequest`, `Craft.appendHeadHtml`/`appendBodyHtml`,
`Craft.initUiElements`, `Craft.CpScreenSlideout`, and `Craft.hasMousePointerEvents`
— accessed via the page globals (`declare const Craft/$`). The chip
dblclick/taphold-to-edit shortcut and the create-button `activate` binding
stay jQuery event wiring (`$(chip).on(...)`, `$createBtn.on('activate', ...)`).

## Files

- `component-select.ts` — the `ComponentSelect` `Base` subclass (all behavior),
  plus the exported `ComponentSelectSettings` and `DefineChipActionsEventDetail`
  interfaces and the module-scoped `wiredChips` WeakSet.
- `component-select.ce.ts` — `<craft-component-select>`, the self-booting
  custom element (a `ControllerElement`) and the public API facade; owns
  attribute parsing (`#parseSettings`/`#boolAttr`) and the `getInputValue`
  backing field.
- `support.ts` — the `componentSelectData` WeakMap (container → controller),
  used for owner-resolution lookups (`closestRegistered`) from chip action
  handlers and the reorder button.
- `index.ts` — registers `<craft-component-select>` and re-exports the element,
  the controller, the settings/event types, and `componentSelectData`.
  Imported (for its side effect) from `resources/js/cp.ts`.

## Chip selection (`selectable`)

Restored on top of the modern **`@craftcms/garnish` `Select`** (the jQuery-free
port of `Garnish.Select`), mirroring how `DragSort` is used. When `selectable`
is on, `ComponentSelect` builds a `Select` over the chips (`component-select.ts`,
`#initSelect`) so a chip can be **clicked / shift-clicked / ⌘-clicked** to build
a selection (`multi` follows `sortable`, legacy `multi: this.settings.sortable`),
and:

- **Backspace/Delete** on a selected chip removes the whole selection
  (`#initChipRemovalShortcut` → `#removeSelected`), matching the legacy
  `addComponents` keydown handler.
- **Dragging a chip that's part of the selection** drags the whole group — the
  `DragSort` `filter` (`#draggeeFilter`) returns every selected chip's `li` when
  the grabbed chip is selected, else just the grabbed `li` (legacy
  `initComponentSort`'s filter).
- A window `mousedown` outside the container clears the selection (legacy
  deselect-on-outside-click); `Select`'s own container-click also deselects.

Selection membership is (re-)added in `init` + the list observer (and removed on
chip removal / observer-remove), the same lifecycle as `DragSort` membership, so
it survives a disconnect/reconnect and chip adoption across selects.

### Default: on, but no checkbox

The `selectable` attribute defaults to **`true`**, matching the legacy
`Craft.ComponentSelectInput` default (`selectable: true`) — the task's "keep
behavior identical to legacy defaults." Legacy achieved this via a *split* the
template preserves: the JS class defaulted `selectable: true` (selection always
on) while the Twig rendered chips with **no** checkbox affordance by default
(`checkbox: selectable ?? false`, with the Twig `selectable` var undefined). The
template reproduces both from the one caller-facing `selectable` var by reading
it with two different fallbacks — `selectable ?? true` for the element's
`selectable` attribute, `selectable ?? false` for the chip `checkbox` — so an
explicit value drives both, while absence gives selection-on + checkbox-off.
Pass `selectable: false` to the Twig (or `selectable="false"` on the element) to
turn selection off; pass `selectable: true` to also render chip checkboxes.

The legacy `addItemsToActionMenus` setting stays folded into always-on.

## Deferred

- Behavioral/browser verification (add/remove/reorder chips, chip selection +
  Backspace/Delete + multi-drag, the Choose menu, the Create-button slideout,
  limit handling, and the `<craft-entry-type-manager>` integration) is left to
  manual testing.
