# nested-element-manager

Port of the legacy `packages/craftcms-legacy/cp/src/js/NestedElementManager.js`
(`Craft.NestedElementManager`) — the controller for nested-element card grids
and embedded element indexes (user addresses, entries-in-entries, and any other
PHP `NestedElementManager::getCardsHtml()` / `getIndexHtml()` surface).

## What changed

- `Craft.NestedElementManager` is now the modern `NestedElementManager` class
  (`nested-element-manager.ts`) on `@craftcms/garnish` `Base`. The legacy
  three-argument constructor signature (`container, elementType, settings`)
  still works; the modern form folds `elementType` into the settings.
- PHP `NestedElementManager::createView()` emits a
  `<craft-nested-element-manager element-type="…" settings="{json}">` wrapper
  around its rendered markup instead of a `HtmlStack::jsWithVars()` boot, so
  the manager self-boots (and re-boots after Inertia fragment swaps) via
  `ControllerElement`.
- Card multi-selection and drag-sorting use modern `@craftcms/garnish`
  `Select` / `DragSort` (same options the legacy `Garnish.Select` /
  `Garnish.DragSort` calls passed).
- The card action-menu items the legacy class injected client-side
  (move up/down, duplicate, copy, delete) now come down **server-rendered**
  with `data-*-action` markers (`ElementHtml::nestedCardActionItems()`,
  requested via the internal `showNestedActions` card config that
  `getCardsHtml()` and `app/render-elements` set); `initElement()` only
  wires their behavior. Copy is the element's own server-wired item,
  intercepted in bulk mode. Paste — clipboard-dependent — is the one
  client-injected item (`Craft.addActionsToChip`). Position/limit/clipboard
  gating is re-synced on every state change (`#syncCardActionItems()`)
  instead of at menu-open time.
- The jQuery `.data('nestedElementManager')` back-reference became the
  `support.ts` WeakMap, keyed by the cards/index container `div`.
- `destroy()` now actually tears down (activate handlers, card listeners,
  Select/DragSort, the deferred element-editor lookup) — the legacy version
  only removed the data reference.

## What stayed jQuery / legacy seams

This class is an orchestrator of still-jQuery Craft widgets, so these seams
survive (via `declare const Craft/$`):

- `Craft.ui.createButton` / `createPasteButton` / `Craft.ui.icon` (jQuery factories)
- the `disclosureMenu()` and `expandableButton()` jQuery plugins (create menu,
  card action menus)
- `Craft.createElementIndex` (index mode is entirely the legacy element index)
- `Craft.createElementEditor` (edit/create slideouts)
- the `Craft.cp` singleton: `announce`, `displayNotice`/`displayError`,
  `copyElements`/`pasteElements`/`getCopiedElements`/`onCopyElements`,
  `elementThumbLoader`
- the owner form's legacy `Craft.ElementEditor`, found via
  `$(form).data('elementEditor')`
- `Craft.sendActionRequest`, `Craft.appendHeadHtml`/`appendBodyHtml`,
  `Craft.Preview`, `Craft.broadcaster`, `Craft.elementTypeNames`,
  `Craft.refreshElementInstances`, `Craft.hasMousePointerEvents`

## Known warts kept for parity

- `Craft.cp.onCopyElements()` has no unregister API, so the copy-listener
  callback keeps the instance reachable until page unload even after
  `destroy()` (legacy parity; the callback only touches the paste button).
- `deleteElement()` passes the card (not its `li`) to `DragSort.removeItems`,
  matching the legacy call — a no-op both before and after.

## Deferred

- The create/paste buttons are still legacy `Craft.ui` jQuery buttons rather
  than `craft-button` web components (they'd need the disclosure/expandable
  plugin equivalents first).
- Index mode remains a thin wrapper over the legacy element index.
