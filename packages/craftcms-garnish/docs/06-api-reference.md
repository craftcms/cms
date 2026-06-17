# 06 — Public API Reference

A concise "what can I call" cheat sheet for the current public surface of
`@craftcms/garnish`. Signatures are simplified for readability; the source TSDoc
(and your editor's IntelliSense) is authoritative. Members marked **PoC** are not
yet implemented and throw, or behave as noted.

Two entry points:

| Import | Surface |
| --- | --- |
| `@craftcms/garnish` | Modern, jQuery-free named exports + the `Garnish` namespace object. |
| `@craftcms/garnish/compat` | Side-effecting: installs `window.Garnish`, restores `.extend()`/`this.base()`/jQuery args. |

---

## `Base`

`abstract class Base<S extends GarnishBaseSettings = GarnishBaseSettings>` — the
base class every component extends. Native `class` + `super`; no `init`/`this.base`
(those are compat-only).

| Member | Signature | Description |
| --- | --- | --- |
| `settings` | `S \| null` | Resolved settings; `null` until `setSettings` runs. |
| `disabled` | `get => boolean` | Whether the instance is disabled. |
| `setSettings` | `(settings?, defaults?) => void` | Shallow-merge defaults ← overrides into `settings`. |
| `on` | `(events, [data,] handler) => void` | Subscribe to instance event(s); supports `.namespace`s. |
| `once` | `(events, [data,] handler) => void` | Like `on`, auto-removed after first fire. |
| `off` | `(events, handler?) => void` | Unsubscribe (namespaced, by type, and/or by handler ref). |
| `trigger` | `(type, data?) => void` | Emit an event to instance + class-level handlers. |
| `addListener` | `(elem, events, [data,] handler) => void` | Bind tracked DOM listener(s); `handler` may be a method-name string. |
| `removeListener` | `(elem, events) => void` | Remove specific DOM listener(s). |
| `removeAllListeners` | `(elem) => void` | Remove all DOM listeners on an element. |
| `enable` / `disable` | `() => void` | Toggle the DOM-listener gate. |
| `destroy` | `() => void` | Emit `destroy`, remove all listeners, clear handlers. |

Event objects passed to handlers are `GarnishEvent` (for DOM events, the native
`Event` augmented with `data`/`target`).

---

## `Modal`

`class Modal extends Base<ModalSettings>` — accessible, animated modal dialog.

**Constructor:** `new Modal(container?, settings?)` — also `new Modal(settings)`
(param shift when the first arg is a plain object).

### Statics

| Member | Type | Description |
| --- | --- | --- |
| `Modal.instances` | `Modal[]` | All live modals. |
| `Modal.visibleModal` | `Modal \| null` | The currently visible modal. |
| `Modal.defaults` | `ModalSettings` | Default settings (below). |
| `Modal.relativeElemPadding` | `number` (`8`) | Legacy parity constant. |

### Settings (`ModalSettings`)

| Key | Default | Description |
| --- | --- | --- |
| `autoShow` | `true` | Show immediately on construction (when a container is given). |
| `draggable` | `false` | **PoC:** throws if enabled. |
| `dragHandleSelector` | `null` | Drag handle selector (PoC). |
| `resizable` | `false` | **PoC:** throws if enabled. |
| `minGutter` | `10` | Min px between modal and viewport edge. |
| `onShow` / `onHide` | no-op | Show/hide callbacks. |
| `onFadeIn` / `onFadeOut` | no-op | Post-fade callbacks. |
| `closeOtherModals` | `false` | Hide any other visible modal on show. |
| `hideOnEsc` | `true` | Hide on Escape. |
| `hideOnShadeClick` | `true` | Hide on backdrop click. |
| `triggerElement` | `null` | Focus-restore target (defaults to focus at construction). |
| `shadeClass` | `'modal-shade'` | CSS class for the shade/backdrop. |

### Methods & properties

| Member | Signature | Description |
| --- | --- | --- |
| `show` / `hide` | `() => void` / `(ev?) => void` | Show / hide with fade. |
| `quickShow` / `quickHide` | `() => void` | Show / hide without fade. |
| `onShow` / `onHide` / `onFadeIn` / `onFadeOut` | `() => void` | Overridable hooks (emit the event + run the callback). |
| `updateSizeAndPosition` | `() => void` | Re-center and re-fit; emits `updateSizeAndPosition`. |
| `getWidth` / `getHeight` | `() => number` | Measure the container (throws if unset). |
| `setContainer` | `(container) => void` | Assign/replace the container. |
| `addLiveRegion` | `() => void` | Append the live region to the container. |
| `destroy` | `() => void` | Remove DOM, drop from `instances`, base teardown. |
| `$container` / `$shade` / `$liveRegion` / `$triggerElement` | `HTMLElement \| Element \| null` | Element refs. |
| `visible` | `boolean` | Whether currently shown. |
| `desiredWidth` / `desiredHeight` | `number \| null` | Optional fixed dimensions. |

**Events:** `show`, `hide`, `fadeIn`, `fadeOut`, `updateSizeAndPosition`, `escape`,
`destroy`.

---

## `UiLayerManager`

`class UiLayerManager extends Base` — manages the stack of UI layers (document,
modals, HUDs, menus) and their scoped keyboard shortcuts. The active instance is a
singleton at `Garnish.uiLayerManager` (created by `initGarnish()`).

| Member | Signature | Description |
| --- | --- | --- |
| `layers` | `Layer[]` | The layer stack (index `0` = base document). |
| `layer` | `get => number` | Topmost layer index. |
| `currentLayer` | `get => Layer` | The active layer. |
| `modalLayers` / `highestModalLayer` | getters | Modal layers / the highest one. |
| `addLayer` | `(container?, options?) => this` | Push a layer; `{bubble}` opt-in for shortcut fall-through. |
| `removeLayer` | `(layer?) => this \| undefined` | Pop the top, or remove a specific layer. |
| `registerShortcut` | `(keyCode \| def, cb, layer?) => this` | Register a keyboard shortcut. |
| `unregisterShortcut` | `(keyCode \| def, layer?) => this` | Remove a shortcut. |
| `triggerShortcut` | `(ev, layerIndex?) => void` | Dispatch a matching shortcut (used internally). |

## `EscManager` — _deprecated_

`class EscManager extends Base` — legacy Escape-key dispatcher. **Deprecated**; use
`UiLayerManager`. `register(obj, fn)`, `unregister(obj)`, `escapeLatest(ev)`.

---

## The `Garnish` namespace object

`import {Garnish} from '@craftcms/garnish'` (also the default export). A single
object carrying every class, constant, utility, and flag — the legacy-shaped
singleton, for incremental migration. **Prefer the tree-shakeable named exports in
new code.**

- **Classes:** `Base`, `Modal`, `UiLayerManager`, `EscManager`, `DragMove`,
  `ShortcutManager` (_deprecated_ alias of `UiLayerManager`).
- **Globals/flags:** `win`, `doc`, `bod`, `scrollContainer` (get/set), `rtl`, `ltr`,
  `activateEventsMuted` (get/set), `resizeEventsMuted` (get/set).
- **Class-level events:** `on(Class, …)`, `off(Class, …)`, `once(Class, …)`.
- **Managers (after `initGarnish()`):** `escManager`, `uiLayerManager`.
- **Custom-event installers:** `installActivate`, `installTextchange`, `installResize`.
- **Icons:** `ResizeHandle` (SVG string).
- Plus every constant and utility listed below, and `muteResizeEvents(cb)`.

`initGarnish(): typeof Garnish` — lazily create + attach the manager singletons
(idempotent).

`muteResizeEvents(callback): void` — run `callback` with `resize` events suppressed.

---

## Utilities (named exports, also on `Garnish`)

### DOM (`utils/dom`)

| Function | Signature | Description |
| --- | --- | --- |
| `coerceElements` | `(input) => EventTarget[]` | jQuery-free `$(x)` coercion. |
| `getElement` | `(input) => EventTarget \| undefined` | First coerced element. |
| `hasAttr` | `(elem, attr) => boolean` | Whether the attribute is present. |
| `getOffset` | `(elem) => {top, left}` | Document-relative offset. |
| `hitTest` | `(x, y, elem) => boolean` | Whether a page point is inside the element. |
| `isCursorOver` | `(ev, elem) => boolean` | Whether the cursor is over the element. |
| `copyTextStyles` | `(source, target) => void` | Copy font/text CSS props. |

### Focusable matcher (`utils/focusable`)

| Function | Signature | Description |
| --- | --- | --- |
| `isFocusable` | `(el) => boolean` | Visible + focusable (incl. `tabindex="-1"`). |
| `isKeyboardFocusable` | `(el) => boolean` | Focusable and Tab-reachable. |
| `getFocusableElements` | `(container) => HTMLElement[]` | Focusable descendants, in order. |
| `isVisible` | `(el) => boolean` | jQuery-`:visible` equivalent. |

### Focus management (`utils/focus`)

| Function | Signature | Description |
| --- | --- | --- |
| `focusIsInside` | `(container) => boolean` | Whether focus is within. |
| `firstFocusableElement` | `(container) => HTMLElement \| null` | First focusable child. |
| `getKeyboardFocusableElements` | `(container) => HTMLElement[]` | Tab-reachable descendants. |
| `getFocusedElement` | `() => Element \| null` | `document.activeElement`. |
| `trapFocusWithin` | `(container) => void` | Install a Tab focus trap. |
| `releaseFocusWithin` | `(container) => void` | Remove the focus trap. |
| `setFocusWithin` | `(container) => void` | Focus the first sensible element (or the container). |

### ARIA (`utils/aria`)

| Function | Signature | Description |
| --- | --- | --- |
| `addModalAttributes` | `(container) => void` | Add `aria-modal`/`role="dialog"`. |
| `ariaHide` | `(element) => void` | `aria-hidden="true"` (restorable). |
| `hideModalBackgroundLayers` | `() => void` | Hide background siblings from AT. |
| `resetModalBackgroundLayerVisibility` | `() => void` | Reverse the above. |
| `isScriptOrStyleElement` | `(element) => boolean` | `<script>`/`<style>` test. |
| `hasJsAriaClass` | `(element) => boolean` | Whether it carries the bookkeeping class. |

### Forms (`utils/forms`)

| Function | Signature | Description |
| --- | --- | --- |
| `getInputBasename` | `(elem) => string \| null` | `name` with `[brackets]` stripped. |
| `getInputPostVal` | `(input) => string \| string[] \| null` | Value as it would POST. |
| `findInputs` | `(container) => HTMLElement[]` | Inputs within (legacy selector wart preserved). |
| `getPostData` | `(container) => Record<string,string>` | Serialize inputs to a POST map. |
| `copyInputValues` | `(source, target) => void` | Copy values input-by-input (skips files). |

### Animation / scroll (`utils/animation`)

| Function | Signature | Description |
| --- | --- | --- |
| `requestAnimationFrame` / `cancelAnimationFrame` | native | Re-exported native RAF. |
| `prefersReducedMotion` | `() => boolean` | Reduced-motion preference. |
| `getUserPreferredAnimationDuration` | `(duration) => number \| string` | `0` if reduced-motion, else the value. |
| `scrollContainerToElement` | `(container, elem?) => void` | Scroll an element into view. |
| `shake` | `(elem, prop?) => void` | WAAPI shake (no-op under reduced-motion). |

### Environment (`utils/env`)

| Function | Signature | Description |
| --- | --- | --- |
| `isMobileBrowser` | `(detectTablets?) => boolean` | UA sniff. **Deprecated** (prefer feature queries). |
| `isPrimaryClick` | `(ev) => boolean` | Primary click, no Ctrl/Meta. |
| `isCtrlKeyPressed` | `(ev) => boolean` | Platform Ctrl/⌘ pressed. |
| `getBodyScrollTop` | `() => number` | Clamped body scrollTop. |

### Misc (`utils/misc`)

| Function | Signature | Description |
| --- | --- | --- |
| `getDist` | `(x1,y1,x2,y2) => number` | Euclidean distance. |
| `within` | `(num,min,max) => number` | Clamp into range. |
| `isString` | `(val) => boolean` | String type guard. |
| `isArray` | `(val) => boolean` | **Deprecated** — use `Array.isArray`. |
| `isTextNode` | `(elem) => boolean` | Text-node test. |
| `log` | `(msg) => void` | **Deprecated** — use `console`. |
| `handleActivatingKeypress` | `(event, cb) => void` | **Deprecated** — use the `activate` event. |

### Custom-event installers (`custom-events`)

| Function | Signature | Description |
| --- | --- | --- |
| `installActivate` | `(el) => () => void` | Install unified click/keyboard `activate`; returns a disposer. |
| `installTextchange` | `(el, {delay?}) => () => void` | Install debounced `textchange`; returns a disposer. |
| `installResize` | `(el) => () => void` | Install dimension-change `resize`; returns a disposer. |

Reached transparently via `addListener(el, 'activate' | 'textchange' | 'resize', fn)`.

### Event system primitives (`events`, `dom-listeners`)

Mostly used through `Base`, but exported for advanced use:

- `EventEmitter` — per-instance object pub/sub (backs `Base.on/off/once/trigger`).
- `ClassEventBus` — class-level pub/sub (backs `Garnish.on/off/once`).
- `DomListenerRegistry` — namespaced DOM-listener registry (backs `addListener`).
- `parseEvents(events, splitOn?)`, `formatDomEvents(events, namespace)` — event-string helpers.
- Types: `GarnishEvent`, `GarnishEventHandler`, `DomListenerOptions`, `ElementInput`,
  `GarnishBaseSettings`, `ActivateOptions`, `TextchangeOptions`.

### Constants (`constants`)

Key codes (`ESC_KEY`, `TAB_KEY`, `RETURN_KEY`, `SPACE_KEY`, `A_KEY`, arrows, …),
mouse buttons (`PRIMARY_CLICK`, `SECONDARY_CLICK`), axes (`X_AXIS`, `Y_AXIS`),
durations (`FX_DURATION`, `SHAKE_STEPS`, `SHAKE_STEP_DURATION`), node types
(`TEXT_NODE`), and ARIA classes (`JS_ARIA_CLASS`, `JS_ARIA_TRUE_CLASS`,
`JS_ARIA_FALSE_CLASS`). Also `VERSION`.

### Globals (`globals`)

`win` (`window`), `doc` (`document`), `bod` (`document.body`) — native refs. The
jQuery-wrapped forms (`$win`/`$doc`/`$bod`/`$scrollContainer`) are **compat-only**.

---

## Compat exports (`@craftcms/garnish/compat`)

Importing the module is **side-effecting**: it builds the legacy-shaped namespace
and assigns `window.Garnish`.

| Export | Signature | Description |
| --- | --- | --- |
| `GarnishCompat` | object (default export) | The assembled legacy-shaped namespace (built on import). |
| `installGarnishCompat` | `() => GarnishCompatNamespace` | Build + install onto `window.Garnish` (guarded, idempotent). |
| `buildGarnishCompat` | `() => GarnishCompatNamespace` | Build the namespace **without** touching `window`. |
| `compatify` | `(ModernClass) => LegacyCtor` | Wrap a modern class so `.extend()` / `init` / `this.base()` work. |
| `resolveJQuery` | `() => JQueryLike \| null` | Detect global jQuery. |
| `isJquery` | `(val) => boolean` | Whether a value is a jQuery collection. |
| `toJq` | `(value) => JQueryCollection` | `$(value)` (throws if jQuery absent). |
| `unwrapJq` | `(value) => unknown` | jQuery collection → first native element. |

**`.extend()` shape:** `LegacyClass.extend(instanceMembers?, staticMembers?)` returns
a real subclass. Inside instance methods, `this.base(...)` calls the ancestor
implementation; `init(...)` runs as the constructor on the most-derived class.
Constructor arguments that are jQuery collections (or selectors) are unwrapped to
native elements before reaching the modern class.

**jQuery-only namespace members** restored by the compat layer: `isJquery`,
`$win`/`$doc`/`$bod`/`$scrollContainer` (lazy getters), a jQuery-wrapped
`getFocusedElement()`, the `$.fn.activate/textchange/resize` chaining sugar, and the
deprecated `Menu` (→ `CustomSelect`, when ported) / `ShortcutManager` (→
`UiLayerManager`) aliases. jQuery is required (and detected) at runtime for these;
absent jQuery, jQuery-only features throw a clear error and the rest degrades
gracefully.

---

## Not yet supported (PoC scope)

- **`Modal` `draggable` / `resizable`** — default `false`; **throw** when enabled.
- **`DragMove`** — placeholder; its constructor **throws** (`BaseDrag` not ported).
