# element-selector-modal

The Vue presentation layer for the element selector, plus what is left of the
legacy modal it replaced.

The business logic does **not** live here — it lives in
`@craftcms/ui`'s [`core/element-selector`](../../../../packages/craftcms-ui/src/core/element-selector/),
and the chrome lives in that package's `craft-element-selector-modal` component.
This folder is the third of the three layers: the Vue view, the Vue-mounting
factory, and the `Craft.*` shims.

## The three layers

```
@craftcms/ui  core/element-selector/     ← no DOM, no Vue, no Lit, no jQuery
                     │
       ┌─────────────┴──────────────┐
       ↓                            ↓
@craftcms/ui                   this folder
  <craft-element-selector-        ElementSelectorModal.vue
   modal>  (chrome only)          (renders the WC, slots the index)
```

**The controller is the seam — not the slot.** The web component and the index
never talk to each other; both talk to the controller. Every user intent goes
*into* the controller, every piece of state comes *out* of it. The web
component's Select button calls `controller.submit()` directly and *also*
dispatches `craft-select` for onlookers — the event is a notification, not the
mechanism. Wire it the other way and one intent ends up with two paths that
drift.

Corollary: `ModalElementIndex.vue` emits `selection-change` / `choose` and never
imports the core. `ElementSelectorModal.vue` does the forwarding, which keeps the
index reusable and `useModalElementIndex` core-agnostic.

## Files

| File | Role |
| --- | --- |
| `ElementSelectorModal.vue` | The Vue view: renders `<craft-element-selector-modal>` with the index slotted in, plus the asset transform menu. |
| `useElementSelectorController.ts` | Mirrors the core's `change` event into a `shallowRef`. Deliberately shallow — nothing wraps the controller's frozen snapshots in a reactive proxy, so Vue and the web component read the same object. |
| `create-element-selector-modal.ts` | The imperative factory. Builds a controller from the registry, mounts the Vue view into a detached host, returns a handle. **Async**, and lazy-imports Vue so a page with a relation field doesn't pay for the index unless a modal opens. |
| `ModalElementIndex.vue` | The index itself. Unchanged in shape from the legacy modal's. |
| `useModalElementIndex.ts` | The composable behind it — the page's own `useElementIndex*` stack with a non-Inertia visitor. |
| `modal-index-visitor.ts` | That visitor. |
| `index.ts` | Registers the asset controller, drains legacy registrations, assigns the `Craft.*` shims. |
| `volume-folder-selector-modal.ts` | **Still on the legacy modal.** See below. |
| `base-element-selector-modal.ts`, `asset-selector-modal.ts`, `registry.ts` | **Legacy.** Only the volume-folder modal still reaches them. |

## What the payload looks like

`onSelect` receives `ElementInfo[]` — the six stable keys plus whatever
`ModalIndexViewModel::typeSpecificRowData()` adds for that element type
(`kind`/`alt` for assets, `folderId` for folders).

Those arrive **nested** under an `elementInfo` key on each row, not merged into
it. A row is otherwise rendered column HTML keyed by attribute, and a flat merge
let a column silently overwrite anything sharing its name — `status` came back as
a `<craft-badge>` element rather than `"pending"`, and `kind` would have come back
as `"Image"` rather than `"image"` whenever the File Kind column was visible.

## The legacy remnant

`volume-folder-selector-modal.ts` still extends `BaseElementSelectorModal`,
because it drives the **legacy jQuery element index** rather than the Vue one:
folder picking keys off the index's `sourcePath` (the breadcrumb of the folder
you have navigated into), which is what "select the folder I'm looking at" means
when no row is highlighted. It is the last thing keeping `ElementIndexHtml` and
the HTML `element-indexes/*` endpoints alive.

`VolumeFolderSelectorController` is already written and tested in the core, and
`<craft-element-selector-modal>` has a `non-modal` mode for exactly this case —
a top-layer dialog paints above `<body>`-appended menus, which is most of the
legacy CP. What remains is the binder: create the element, slot
`response.data.html` into it, boot `Craft.createElementIndex`, and adapt it to
`VolumeFolderIndexAdapter`. Deleting the three legacy files falls out of that.

## Registry

The map lives in the core (`@craftcms/ui`, `registry.ts`) and is keyed to
controller classes. `index.ts` registers `AssetSelectorController` for assets and
drains anything a plugin left on `Craft._elementSelectorModalClasses` before this
module evaluated — the legacy bundle is a plain `<script>` and runs first.

`Craft.createElementSelectorModal` is the modern factory, so it is now **async**
and resolves to a handle rather than a modal object.

## Gotchas

- **The factory has to register `CpLink` on its app.** The index renders server
  HTML through `DynamicHtmlRenderer`, which compiles at runtime, so every
  component that HTML names must be registered on *that* app. `cpComponentRegistry`
  is the shared singleton; `createCpComponentRegistry()` builds an empty one, and
  calling it was why every title cell used to render as an inert `<cplink>`.
- **`craft-action-menu` invokers must stay under `v-once`.** It relocates its own
  light DOM, and Vue re-patching that throws `insertBefore` of null. The transform
  menu therefore puts its reactive `disabled` on the *items* (a prop the web
  component consumes as data), never on the invoker.
- **`@craftcms/ui` is consumed two ways.** Vite dev reads its `src`; `vue-tsc`
  resolves to `dist`. Run `npm run build:ui` after touching the package or the
  root typecheck won't see it.
