# Garnish Core — Implementation Notes

> Status: **Implemented.** This documents what was actually built against the
> `01-core-design.md` contract: deviations, decisions, and the exact exported
> surface the Modal and compat engineers will consume. All three gates are green:
> `check:types`, `test` (70 tests), `build`.

## Module layout (as built)

Matches doc 01 §6, with two additions:

- `src/managers/registry.ts` — a tiny manager-singleton registry. Breaks the
  circular import between `utils/aria.ts` (needs the active UI layer manager) and
  `managers/ui-layer-manager.ts`. `initGarnish()` calls `setUiLayerManager()`.
- `src/icons/resize-handle.ts` — `ResizeHandle` SVG export (doc 03 §1 Tier 1).

## Exact exported names / signatures (for Modal + compat)

### Classes

- `abstract class Base<S extends GarnishBaseSettings = GarnishBaseSettings>`
  - `settings: S | null` (null until `setSettings`)
  - `setSettings(settings?: Partial<S>, defaults?: Partial<S>): void` — **shallow** `Object.assign({}, base, defaults, settings)`.
  - `on(events, handler)` / `on(events, data, handler)`
  - `once(...)`, `off(events, handler?)`
  - `trigger(type, data?)` — two-pass: instance emitter THEN `garnishClassBus.dispatch`.
  - `addListener(elem, events, dataOrHandler, handler?)` — `dataOrHandler`/`handler` may be a **method-name string** resolved against `this`.
  - `removeListener(elem, events)`, `removeAllListeners(elem)`
  - `enable()`, `disable()`, `get disabled()`, `destroy()`
  - protected: `_disabled`, `_namespace` (`.Garnish<uuid>`), `_emitter`, `_domListeners`.
- `class EscManager extends Base` — `register(obj, fn|methodName)`, `unregister(obj)`, `escapeLatest(ev)`.
- `class UiLayerManager extends Base` — `layers`, getters `layer`/`currentLayer`/`modalLayers`/`highestModalLayer`,
  `addLayer(container?|options, options?)`, `removeLayer(layer?)`, `getLayerIndex`, `removeLayerAtIndex`,
  `registerShortcut(keyCode|def, cb, layer?)`, `unregisterShortcut`, `triggerShortcut(ev, layerIndex?)`.
  **Layer shape:** `{$container: Element|null, shortcuts, isModal?, options:{bubble}}` — `$container` is a **native
  Element** (NOT a jQuery collection). The compat layer must wrap it with `$()` where consumers expect `.get(0)` etc.

### Event system (`events.ts`)

- `parseEvents(events, splitOn=' '|',')` → `{type, namespace}[]`. Splits on first `.`. `''` type allowed for the
  namespace-only `off` form.
- `formatDomEvents(events, namespace)` → space-joined string with namespace appended (comma-split input).
- `class EventEmitter<Target>` — `on/once/off/trigger/clear`. Trigger precedence: `{data: regData} <- triggerData <- {type, target}`.
- `class ClassEventBus` — `on/once/off(target, events, [data], handler)`, `dispatch(instance, type, data)`, `clear()`.
  `console.warn` on `undefined` target (wart #7). `dispatch` filters by `instance instanceof reg.target`.
- The single shared instance is `garnishClassBus` (in `globals.ts`); `Garnish.on/off/once` proxy to it.

### DOM listeners (`dom-listeners.ts`)

- `class DomListenerRegistry` ctor takes `host: { readonly disabled: boolean }` (structural — `Base` satisfies it).
  Methods: `add(elements, events, handler, options?)`, `remove(elements, events)`, `removeAllOn(elements)`, `removeAll()`.
- `DomListenerOptions`: `{delegate?, data?, capture?, passive?}`. Delegation uses `event.target.closest(selector)` +
  `container.contains`. The delegated match is exposed on the event as `garnishTarget` (we cannot overwrite the native
  read-only `currentTarget`/`target`).
- `ElementInput` coercion lives in `utils/dom.ts` `coerceElements()` (string → `querySelectorAll`, node/window → `[node]`,
  array/NodeList → flattened, null → `[]`).

### Custom events (`custom-events/`)

- `installActivate(el)`, `installTextchange(el, {delay?})`, `installResize(el)` — each returns a **ref-counted disposer**
  (idempotent per element/type). `addListener(el, 'activate'|'textchange'|'resize', fn)` auto-routes through these.
- Synthetic events are dispatched as native `CustomEvent`s on the element; `activate` carries `originalEvent`.
- `installResize` shares one lazily-created `ResizeObserver`; `window` uses native `resize` (install is a no-op).

### Globals (`globals.ts`)

- Named exports `win` (= `window`), `doc` (= `document`), `bod` (= `document.body`), `garnishClassBus`.
- `globals` object holds mutable `scrollContainer`, `activateEventsMuted`, `resizeEventsMuted`, `rtl`, `get ltr`.
  The `Garnish` namespace exposes these via get/set accessors so flag mutations stay live.

### `Garnish` namespace object + `initGarnish()`

`index.ts` exports the legacy-shaped `Garnish` object (constants spread, `win/doc/bod`, accessor-backed flags, classes,
`on/off/once`, `_normalizeEvents` alias, all `utils/*`, `muteResizeEvents`, `ResizeHandle`, plus `escManager`/
`uiLayerManager` slots). `initGarnish()` instantiates the two managers (idempotent) and registers the layer manager.
**The core never assigns `window.Garnish`** — that's the compat layer's job.

## Deviations & decisions (read these)

1. **`once` removal-by-handler is wrapper-closure (legacy wart #6).** `off(events, originalHandler)` does **NOT** remove
   a `once` registration — only the internal `onceler` wrapper matches. Remove a pending `once` by type/namespace.
   (Contract §2.3 mandated the wrapper-closure approach for identical `off` behavior.)
2. **Event objects are the native DOM event, augmented.** For DOM listeners we pass the real `Event` through and
   `Object.defineProperty` only the **missing** Garnish fields (`data`, `garnishTarget`). We never write `type`/`target`
   (read-only native getters; writing throws in happy-dom and real browsers). Pub/sub (`EventEmitter`) events are plain
   objects as specified.
3. **`getOffset`/`hitTest` drop the legacy instance-cache fields** (`_offset`, `_$elem`, `_x1`…) and use
   `getBoundingClientRect()` + `scrollX/Y`. Behaviorally equivalent.
4. **`shake` uses WAAPI** (`element.animate`) over a single keyframe ramp instead of the legacy 11× `setTimeout`+Velocity
   loop; gated by `prefersReducedMotion` (no-op). `scrollContainerToElement` uses instant `scrollTop`/`window.scrollTo`
   (Velocity removed) — no smooth-scroll tween.
5. **`isPrimaryClick`** reads `ev.button === 0` for native events but honors legacy `ev.which === 1` when present.
6. **`getFocusedElement()` returns `Element | null`** (not a jQuery collection) — compat wraps with `$()` (doc 03 §6).
7. **Focusable matcher** (`utils/focusable.ts`) models jQuery-UI `:focusable`: `<a>/<area>` with href, non-disabled form
   controls, anything with a numeric tabindex, plus a visibility gate (`offsetWidth/Height || getClientRects().length`,
   and no `visibility:hidden/collapse` on self or ancestors). `getFocusableElements` excludes the container itself
   (matches `.find()`). **Note for tests in happy-dom:** there is no layout, so `offsetWidth/Height` are 0 — tests stub
   `getClientRects` to assert focusability. Real browsers don't need this.
8. **`findInputs` keeps the bogus `text` tag** in its selector (wart #3) — matches nothing, preserved verbatim.
9. **`getInputPostVal`** returns `''` (not `null`) for a null-valued `<select>` (wart #4); multi-selects read
   `select.selectedOptions`.
10. **`_namespace`** uses `crypto.randomUUID()` with a `Math.random` fallback (wart #5). It is bookkeeping only — the
    registry's tuple array is the real removal mechanism, so namespaced `.off` works without it.

## What's intentionally NOT here (compat layer's job)

- No `window.Garnish` assignment, no `$.fn.activate/textchange/resize` sugar, no `$.event.special`, no `isJquery`.
- No `Base.extend()` / `this.base()` / `init()` trampoline — subclasses use native `constructor` + `super()`.
- No jQuery anywhere; no Velocity.

## For the Modal engineer specifically (doc 03 §1 checklist — all satisfied)

`Base`, instance pub/sub + DOM registry, `win`/`doc`/`bod`, constants (`ESC_KEY`/`FX_DURATION`/`rtl`/`ltr`),
`UiLayerManager` (`addLayer`/`removeLayer`/`registerShortcut`/`currentLayer`), ARIA/focus helpers
(`addModalAttributes`, `hideModalBackgroundLayers`, `resetModalBackgroundLayerVisibility`, `trapFocusWithin`,
`setFocusWithin`, `getFocusedElement`, `getKeyboardFocusableElements`), the focusable matcher, and `ResizeHandle` are
all exported. `BaseDrag`/`DragMove` are out of scope (PoC defaults `draggable:false`/`resizable:false`).
