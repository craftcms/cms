# Garnish Core — Modern Design Spec (`garnish-core`)

> Status: **Design contract.** This document is the spec the implementation team builds against. It covers the
> foundation layer (`garnish-core`) that every other Garnish module depends on: the class system, the event system,
> the utility surface, the settings system, and the module layout.
>
> Scope boundary: This is the **modern, jQuery-free, ESM/TypeScript** core. The legacy `Base.extend()` contract,
> jQuery-collection arguments, and the legacy global `window.Garnish` object are **out of scope** here — they will be
> restored by a separate, opt-in **compat layer** (`garnish-compat`). Wherever a legacy behavior is mentioned, it is
> only to define the boundary the compat layer will bridge.

---

## 0. Source-of-truth inventory (what the legacy core actually is)

The legacy core is four files:

| File | Role |
| --- | --- |
| `src/lib/Base.js` | Dean Edwards `Base.js` 1.1a — the `.extend(instance, static)` class/prototype system with `this.base()` super-call support. |
| `src/Base.js` | `Garnish.Base` — extends Dean Edwards Base. Settings system, instance-level pub/sub events (`on`/`off`/`once`/`trigger`), namespaced DOM listeners (`addListener`/`removeListener`/`removeAllListeners`), `disable`/`enable`, `destroy`. |
| `src/Garnish.js` | The `Garnish` singleton object — ~40 utilities + constants, the class-level event bus (`Garnish.on/off/once`), `_normalizeEvents`, jQuery `$.event.special` registrations (`activate`, `textchange`, `resize`), focus/ARIA helpers, animation helpers, browser/mobile detection, form/post-data helpers. |
| `src/EscManager.js`, `src/UiLayerManager.js` | Singleton-ish managers attached to the core (`Garnish.escManager`, `Garnish.uiLayerManager`). They are `Base` subclasses and exercise the event + listener API. |

Two distinct event systems coexist in the legacy code and **must both be reproduced**:

1. **Pub/sub object events** — `Garnish.Base.prototype.on/off/once/trigger`, plus a **class-level** variant
   `Garnish.on/off/once`. These are *not* DOM events; they are an in-memory observer registry. `trigger` dispatches to
   instance handlers **and** to class-level handlers whose `target` the instance `instanceof`-matches.
2. **DOM listeners** — `Garnish.Base.prototype.addListener/removeListener/removeAllListeners`, which today are thin
   wrappers over `jQuery.on/off` with a per-instance namespace and a `_disabled` gate.

The modern core keeps both, expressed as one coherent emitter design (§2).

---

## 1. Class system

### 1.1 What legacy does

`lib/Base.js` implements Dean Edwards inheritance:

- `Klass = Base.extend(instanceMembers, staticMembers)` returns a constructor.
- Inside any method, `this.base(...)` calls the overridden ancestor method (re-bound per call via the `extend`
  wrapper that detects `/\bbase\b/` in the function source).
- `Garnish.Base` adds a `constructor` that initializes `_eventHandlers`, `_namespace`, `_listeners`, then calls
  `this.init.apply(this, arguments)`. Subclasses override `init`, **not** `constructor`.

### 1.2 Modern replacement: native `class extends`

The modern core uses native ES classes. The `init()` indirection is **dropped**; subclasses use a real
`constructor` and call `super(...)`. `this.base()` is replaced by `super.method()`.

```ts
// base.ts
export interface GarnishBaseSettings {
  [key: string]: unknown;
}

export abstract class Base<S extends GarnishBaseSettings = GarnishBaseSettings> {
  /** Resolved settings (defaults <- passed settings). Null until setSettings runs. */
  settings: S | null = null;

  protected _disabled = false;

  /** Pub/sub registry (see §2). */
  protected readonly _emitter = new EventEmitter(this);

  /** Tracked DOM listener bindings for bulk teardown (see §2.4). */
  protected readonly _domListeners = new DomListenerRegistry();

  constructor() {
    // No init() trampoline. Subclasses do their own setup after super().
  }

  // ... on/off/once/trigger/addListener/... delegate to _emitter / _domListeners (§2)

  disable(): void { this._disabled = true; }
  enable(): void { this._disabled = false; }
  get disabled(): boolean { return this._disabled; }

  destroy(): void {
    this.trigger('destroy');
    this._domListeners.removeAll();
    this._emitter.clear();
  }
}
```

**Subclass shape (modern):**

```ts
export class EscManager extends Base {
  private handlers: Array<{ obj: unknown; func: EscHandler }> = [];

  constructor() {
    super();
    this.addListener(document.body, 'keyup', (ev) => {
      if ((ev as KeyboardEvent).key === 'Escape') this.escapeLatest(ev as KeyboardEvent);
    });
  }
}
```

### 1.3 Boundary with the compat layer

The compat layer is responsible — and the modern core is explicitly **not** — for:

- A `Base.extend(instance, static)` shim that produces a class wrapping the modern `Base`.
- The `init()` trampoline (compat `extend` maps a passed `init` member to post-`super()` invocation).
- `this.base(...)` super dispatch (compat synthesizes it; modern code uses `super.`).
- Re-exposing every class on `window.Garnish`.

The modern core MUST keep its constructors plain and its method names stable so the compat layer can wrap them
mechanically. No modern core method should depend on the `init()` indirection existing.

### 1.4 Key decisions

- `Base` is `abstract`. It is never instantiated directly.
- Settings are generic (`Base<S>`) so subclasses get typed `this.settings`.
- Constants that were prototype members in legacy (none of significance) become real fields.
- Getters (e.g. `UiLayerManager.layer`, `currentLayer`) become native `get` accessors — already legal in legacy via
  Dean Edwards descriptor copying, so this is a 1:1 move.

---

## 2. Event system

This is the load-bearing part. The modern emitter must reproduce three legacy behaviors:

- **A. Object pub/sub** (`on('foo.ns', data, handler)`, `trigger('foo', data)`) — in-memory, not DOM.
- **B. Class-level pub/sub** (`Garnish.on(TargetClass, 'foo', handler)`) — dispatched on `trigger` to any instance
  that is `instanceof TargetClass`.
- **C. Namespaced DOM delegation** (`addListener(el, 'click.ns', fn)`) — currently `jQuery.on`, including delegated
  selectors and a per-instance namespace for bulk `.off`.

### 2.1 Event-string grammar (preserved exactly)

Legacy `_normalizeEvents`:

- `on`/`off`/`trigger` strings split on **spaces** → multiple events.
- Each event splits on the **first** `.` → `[type, namespace]`. (`'click.foo'` → `type='click'`, `ns='foo'`.)
- `addListener` additionally splits the events arg on **commas** (`_splitEvents`) and appends the instance
  `_namespace` to each before handing to jQuery (`_formatEvents`).

Modern core keeps the same grammar so call sites are unchanged. Canonical parser:

```ts
// events.ts
export interface ParsedEvent {
  type: string;          // '' is invalid for on/trigger; '' with a namespace is the "remove by namespace" form for off
  namespace: string | null;
}

/** 'click.ns mousedown' -> [{type:'click',namespace:'ns'},{type:'mousedown',namespace:null}] */
export function parseEvents(events: string | string[], splitOn: ' ' | ',' = ' '): ParsedEvent[];

/** Format for DOM binding: appends instance namespace. 'click,drag' + '.Garnish123' -> 'click.Garnish123 drag.Garnish123' */
export function formatDomEvents(events: string | string[], namespace: string): string;
```

Note the legacy split-character inconsistency (`on` uses space, `addListener` uses comma). The modern API
**preserves both**: `parseEvents(..., ' ')` for pub/sub, `parseEvents(..., ',')` for `addListener`. This is a
deliberate compatibility wart, documented so the compat layer needn't special-case it.

### 2.2 Public types

```ts
// events.ts

/** Payload an emitted event carries to handlers. */
export interface GarnishEvent<T = unknown, Target = unknown> {
  type: string;
  target: Target;          // the emitting object (legacy: the Base instance)
  data: Record<string, unknown>;   // per-registration data, merged with trigger-time data
  originalEvent?: Event;   // present when re-emitting a DOM event (legacy 'activate' etc.)
  [extra: string]: unknown; // trigger-time payload is spread on (legacy $.extend behavior)
}

export type GarnishEventHandler<E extends GarnishEvent = GarnishEvent> = (event: E) => void;

interface Registration {
  type: string;
  namespace: string | null;
  data: Record<string, unknown>;
  handler: GarnishEventHandler;
  once: boolean;
}
```

### 2.3 Object pub/sub — `EventEmitter`

Backs `Base.on/off/once/trigger`. One instance per `Base` object, owns `this` as the event target.

```ts
// events.ts
export class EventEmitter<Target = unknown> {
  constructor(private readonly target: Target) {}

  on(events: string, handler: GarnishEventHandler): void;
  on(events: string, data: Record<string, unknown>, handler: GarnishEventHandler): void;

  once(events: string, handler: GarnishEventHandler): void;
  once(events: string, data: Record<string, unknown>, handler: GarnishEventHandler): void;

  /** Remove by (type [+ namespace] [+ handler]). Omitting handler removes all of that type/namespace. */
  off(events: string, handler?: GarnishEventHandler): void;

  /** Dispatch. `data` is merged onto the event object after the per-registration `data`. */
  trigger(type: string, data?: Record<string, unknown>): void;

  /** Remove every registration (used by destroy). */
  clear(): void;
}
```

**Semantics that must match legacy:**

- `on`/`once` argument overload: if arg 2 is a function, it's the handler and `data = {}`.
- `trigger` builds `{ type, target: this }`, and for each matching handler constructs the event as
  `{ data: registration.data, ...triggerData, type, target }`. In legacy this is
  `$.extend({data: handler.data}, data, ev)` — i.e. trigger-time `data` keys win over registration `data`, and
  `type`/`target` always win. **Preserve this precedence exactly** (it is observable; some call sites pass `target`
  overrides in `data`, which legacy `$.extend` lets the `ev` object override last).
- `off` matching: a registration matches when `type` matches **and** (the parsed namespace is empty **or** namespaces
  match) **and** (handler is omitted **or** handler is `===`). Iterate **backwards** and splice, like legacy.
- `once` is implemented as a self-removing wrapper registered via `on`; `off(events, wrapper)` removes it. Modern impl
  uses the `once: boolean` flag on `Registration` instead of a wrapper closure, but `off` by original handler must
  still work — so store the original handler reference for `once` matching too. (Simplest faithful approach: keep the
  wrapper-closure approach exactly as legacy does, with `off(events, onceler)`.)

> **Decision:** keep the legacy wrapper-closure `once` to guarantee identical `off` behavior, rather than the flag.
> The flag is a footgun for `off`-by-handler.

### 2.4 Class-level pub/sub — `ClassEventBus`

Backs `Garnish.on/off/once`. Legacy stores these in `Garnish._eventHandlers` (a flat array) and `Base.trigger`
walks it filtering on `this instanceof handler.target`.

```ts
// events.ts
type Constructor<T = object> = abstract new (...args: any[]) => T;

export class ClassEventBus {
  private readonly registrations: Array<Registration & { target: Constructor }> = [];

  on(target: Constructor, events: string, handler: GarnishEventHandler): void;
  on(target: Constructor, events: string, data: Record<string, unknown>, handler: GarnishEventHandler): void;

  once(target: Constructor, events: string, handler: GarnishEventHandler): void;
  once(target: Constructor, events: string, data: Record<string, unknown>, handler: GarnishEventHandler): void;

  off(target: Constructor, events: string, handler?: GarnishEventHandler): void;

  /** Called by Base.trigger: dispatch class-level handlers whose target the instance is an instanceof. */
  dispatch(instance: object, type: string, data: Record<string, unknown>, baseEvent: GarnishEvent): void;
}
```

**Wiring:** `Base.trigger(type, data)` does two passes (exactly like legacy):

1. Its own `_emitter.trigger(type, data)`.
2. `garnishClassBus.dispatch(this, type, data, baseEvent)`.

The single shared `ClassEventBus` instance lives in the core module (`garnishClassBus`) and is exposed as
`Garnish.on/off/once` on the namespace object (§5.3). Legacy guards `on/once` against `undefined` target with a
`console.warn`; **preserve** the warning.

### 2.5 DOM listeners — `DomListenerRegistry`

Replaces `addListener`/`removeListener`/`removeAllListeners`. Legacy leans on jQuery for: element coercion
(`$(elem)`), namespaced binding, delegated binding (the `data` arg can carry a selector via jQuery's
`(events, selector, data, handler)` overloads — though Garnish's own `addListener` only uses `(events, data, handler)`
with a plain-object `data`), and the `_disabled` gate.

Modern, jQuery-free contract:

```ts
// dom-listeners.ts
export type ElementInput = EventTarget | EventTarget[] | NodeListOf<Element> | string | null | undefined;

export interface DomListenerOptions {
  /** Delegated target selector; handler fires only when event.target matches/closest this selector. */
  delegate?: string;
  /** Debounce/throttle hooks used by textchange etc. (see §3 custom events). */
  data?: Record<string, unknown>;
  capture?: boolean;
  passive?: boolean;
}

export class DomListenerRegistry {
  constructor(private readonly host: Base) {}

  /** Bind. `events` uses the comma/space grammar (§2.1). Handler is invoked with the host's `this`
   *  and short-circuited while host.disabled is true (legacy _disabled gate). */
  add(
    elements: ElementInput,
    events: string,
    handler: GarnishEventHandler,
    options?: DomListenerOptions,
  ): void;

  /** Remove specific event(s) previously bound by this host on the element. */
  remove(elements: ElementInput, events: string): void;

  /** Remove all listeners this host bound on the element (legacy removeAllListeners). */
  removeAllOn(elements: ElementInput): void;

  /** Remove everything this host bound anywhere (used by destroy). */
  removeAll(): void;
}
```

**`Base` thin wrappers (signature-compatible with legacy):**

```ts
addListener(elem: ElementInput, events: string, dataOrHandler: object | GarnishEventHandler | string, handler?: GarnishEventHandler | string): void;
removeListener(elem: ElementInput, events: string): void;
removeAllListeners(elem: ElementInput): void;
```

Legacy `addListener` quirks to reproduce:

- Bails silently if no elements resolve.
- Param mapping: `(elem, events, func)` vs `(elem, events, data, func)`; if `func` is undefined and `data` is not a
  plain object, treat `data` as `func`.
- `func` may be a **method name string** (`this[func].bind(this)`) — preserve, since `UiLayerManager` uses
  `addListener(..., 'triggerShortcut')`.
- Handler is invoked only when `!this._disabled`.
- Tracks bound elements so `destroy` can clean them all up.

**Implementation note (no jQuery):** Native `addEventListener` has no namespace concept. The registry must store
`{element, type, namespace, wrappedHandler, capture}` tuples so it can replicate jQuery's namespaced `.off`. Delegation
is implemented with `event.target.closest(selector)` inside the wrapped handler. The per-instance `_namespace` is kept
as a bookkeeping string but is **internal only** in the modern path (it exists in legacy purely to drive jQuery
namespaced removal; here the registry array does that job). The namespace string is still parsed from the event string
for API compatibility and for `removeAllListeners` semantics.

### 2.6 `_namespace`

Legacy: `'.Garnish' + Math.floor(Math.random()*1e9)`, used only to scope jQuery DOM listeners. Modern core keeps a
`protected readonly _namespace: string` for compat-layer parity and debugging, but the registry's tuple list is the
real removal mechanism. Generation should use `crypto.randomUUID?.()` with a `Garnish` prefix, falling back to the
legacy random scheme.

---

## 3. Custom DOM events (`activate`, `textchange`, `resize`)

Legacy registers these through `$.event.special` and adds `$.fn.activate/textchange/resize` chaining sugar. These are
**core-owned custom events** and must move into `garnish-core` (other modules and the legacy CP depend on them
heavily, especially `activate`).

The modern core cannot use `$.event.special`. Design: a small **custom-event installer** that, given a real
`EventTarget`, wires the underlying native listeners and re-dispatches a synthetic `CustomEvent`.

```ts
// custom-events/index.ts
export interface ActivateOptions { /* none today */ }
export interface TextchangeOptions { delay?: number | null; }

/** Attach Garnish 'activate' semantics to an element (mousedown/click/keydown -> 'activate' CustomEvent).
 *  Returns a disposer. */
export function installActivate(el: HTMLElement): () => void;

/** Attach 'textchange': fires a 'textchange' CustomEvent when the input's value changes
 *  (keypress/keyup/change/blur), with optional debounce via options.delay. */
export function installTextchange(el: HTMLElement & { value: string }, options?: TextchangeOptions): () => void;

/** Attach 'resize' via a shared ResizeObserver; window uses native 'resize'. Returns a disposer. */
export function installResize(el: HTMLElement): () => void;
```

Behaviors to preserve from legacy:

- **activate:** `mousedown` on `<button>`/`role=button` calls `preventDefault` (no focus-on-click). `click` and
  `keydown(Space/Enter)` dispatch `activate` unless the element is `.disabled` or `Garnish.activateEventsMuted`.
  Ctrl/⌘-click on an `<a href>` (non-`#`/non-empty) is ignored. Sets `tabindex="0"` on non-disabled, non-body
  elements; removes `tabindex` on disabled (unless `.read-only`). `keydown` ignores bubbled events
  (`this === target`).
- **textchange:** stores last value, compares on each event, dispatches `textchange` only on change. Supports a
  `delay` debounce (legacy reads delay from `data.delay` or `ev.data.delay`).
- **resize:** window → native `resize`. Other elements → shared `ResizeObserver` keyed by stored
  `{width,height}`; only dispatch when dimensions actually change and `Garnish.resizeEventsMuted` is false. The
  observer is lazily created and shared (one per core).

The shared `Garnish.activateEventsMuted` / `Garnish.resizeEventsMuted` flags (and `muteResizeEvents(cb)`) stay on the
namespace object (§4 / §5.3).

> The `DomListenerRegistry.add` recognizes these synthetic event types and routes binding through the matching
> `install*` so that `addListener(el, 'activate', fn)` keeps working. Installation is idempotent per element/type.

---

## 4. Utilities inventory (every public member of `Garnish`)

Legend for jQuery dependency: **none** (no jQuery), **trivial** (only `$(elem)` coercion / `.attr` / `.css` — mechanical
to port), **deep** (jQuery semantics like `:focusable`, `.velocity()`, `.scrollParent()`, `$.event`, `$.data` that need
real reimplementation or a decision).

### 4.A Keep as-is (pure JS, no jQuery)

| Member | Signature | Behavior |
| --- | --- | --- |
| `getDist` | `(x1,y1,x2,y2: number) => number` | Euclidean distance. **none.** |
| `within` | `(num,min,max: number) => number` | Clamp. **none.** |
| `isString` | `(val) => boolean` | `typeof === 'string'`. **none.** |
| `isArray` | `(val) => boolean` | `Array.isArray`. **none.** *(deprecate, §4.D)* |
| `isTextNode` | `(elem) => boolean` | `nodeType === 3`. **none.** |
| `isPrimaryClick` | `(ev) => boolean` | `ev.which === 1 && !ctrl && !meta`. **none** (but `which` → see note). |
| `isCtrlKeyPressed` | `(ev) => boolean` | ⌘ on Mac else Ctrl. **none.** |
| `prefersReducedMotion` | `() => boolean` | `matchMedia('(prefers-reduced-motion: reduce)')`. **none.** |
| `getUserPreferredAnimationDuration` | `(duration) => number\|string` | `0` if reduced motion else `duration`. **none.** |
| `getBodyScrollTop` | `() => number` | Clamped body scrollTop. **trivial** (`$bod.outerHeight`, `$win.height` → `document.body.offsetHeight`, `window.innerHeight`). |
| `requestAnimationFrame` / `cancelAnimationFrame` | `(fn)` / `(id)` | Vendor-prefixed RAF shims. **none** — modern: just re-export native `requestAnimationFrame`/`cancelAnimationFrame` (prefixes long dead). |
| `isMobileBrowser` | `(detectTablets?: boolean) => boolean` | UA regex + memoized `_isMobileBrowser`/`_isMobileOrTabletBrowser`. **none.** Keep, but flag for review (UA sniffing). |
| `log` | `(msg) => void` | `console.log`. **none.** *(deprecate, §4.D)* |
| `muteResizeEvents` | `(cb) => void` | Set flag, run cb, restore. **none.** |
| `_normalizeEvents` | `(events) => ParsedEvent[]` | Event-string parser. **none.** Becomes `parseEvents` (§2.1); kept as `_normalizeEvents` alias for compat. |

> Note on `isPrimaryClick`: `ev.which` is deprecated in the DOM. Modern impl should read `ev.button === 0` (primary)
> while accepting the legacy `which` shape for compat. Keep `PRIMARY_CLICK`/`SECONDARY_CLICK` constants for the compat
> layer but document that native code should use `MouseEvent.button`.

### 4.B Needs rewrite without jQuery

| Member | Signature | Behavior | jQuery dep |
| --- | --- | --- | --- |
| `isJquery` | `(val) => boolean` | `val instanceof $`. | **deep** — meaningless without jQuery. Modern: move to compat layer; core may keep a stub returning `false`, or drop. **Decision: drop from core, provide in compat.** |
| `hasAttr` | `(elem, attr) => boolean` | attr present and not `false`. | **trivial** — `el.hasAttribute(attr)`. |
| `getOffset` | `(elem) => {top,left}` | Element offset, adjusted for non-window scroll container. | **trivial** — `getBoundingClientRect()` + `window.scrollX/Y`; drop the cached `_offset` field. |
| `hitTest` | `(x,y,elem) => boolean` | Point-in-bounds test using offset + outer size. | **trivial** — `getBoundingClientRect()`; uses page coords so add `scrollX/Y`. Drop the `_$elem/_offset/_x1...` instance-cache fields. |
| `isCursorOver` | `(ev, elem) => boolean` | `hitTest(ev.pageX, ev.pageY, elem)`. | **trivial.** |
| `copyTextStyles` | `(source, target) => void` | Copies 10 font/text CSS props. | **trivial** — `getComputedStyle` + assign to `target.style`. |
| `addModalAttributes` | `(container) => void` | Sets `aria-modal=true`, `role=dialog`. | **trivial** — `setAttribute`. |
| `ariaHide` | `(element) => void` | Sets `aria-hidden=true`, stores prior value in a class. | **trivial.** |
| `hasJsAriaClass` | `(element) => boolean` | Has any of 3 JS_ARIA classes. | **trivial** — `classList.contains`. |
| `isScriptOrStyleElement` | `(element) => boolean` | tagName SCRIPT/STYLE. | **trivial.** |
| `hideModalBackgroundLayers` | `() => void` | aria-hide body children except notifications/topmost layer. | **deep** — depends on `uiLayerManager.currentLayer.$container` + body children iteration. Rewrite with `document.body.children` + `el.closest`. |
| `resetModalBackgroundLayerVisibility` | `() => void` | Restore aria-hidden after modal close. | **deep** — class-selector queries + uiLayerManager. Rewrite with `querySelectorAll`. |
| `focusIsInside` | `(container) => boolean` | container contains `:focus`. | **trivial** — `container.contains(document.activeElement)`. |
| `firstFocusableElement` | `(container) => Element` | First `:focusable`. | **deep** — `:focusable` is a jQuery-UI selector. Needs a real focusable-element matcher (see §4.E). |
| `getKeyboardFocusableElements` | `(container) => Element[]` | `:focusable` filtered by `isKeyboardFocusable`. | **deep** — same matcher dependency. |
| `isKeyboardFocusable` | `(element) => boolean` | `:focusable` and tabindex !== -1. | **deep** — focusable matcher. |
| `trapFocusWithin` | `(container) => void` | Tab/Shift+Tab cycles focus inside container; binds `keydown.focus-trap`. | **deep** — `:focusable` + namespaced `.off('.focus-trap')`. Rewrite using the matcher + `DomListenerRegistry` with a `focus-trap` namespace. |
| `releaseFocusWithin` | `(container) => void` | `off('.focus-trap')`. | **deep** — pairs with above. |
| `setFocusWithin` | `(container) => void` | Focus first sensible focusable, else container with tabindex -1. Includes the `.field:visible` heuristic (cms#15245). | **deep** — `:focusable` + `:visible` + `.field` traversal. Needs matcher + visibility check. |
| `getFocusedElement` | `() => Element\|null` | `document.activeElement`. | **trivial** — return `document.activeElement` (legacy returned a `$(':focus')` collection; modern returns the element). |
| `scrollContainerToElement` | `(container, elem?) => void` | Scrolls container so elem is in view. | **deep** — `.scrollParent()`, `.velocity('scroll')`, offset math. Rewrite with `Element.scrollIntoView`/manual `scrollTop` + a real scroll-parent finder; drop Velocity. |
| `shake` | `(elem, prop?='margin-left') => void` | 10-step shake animation via Velocity. | **deep** — Velocity. Rewrite with Web Animations API (`element.animate`) or rAF loop; respect `prefersReducedMotion`. |
| `getElement` | `(elem) => Element` | First element of array/collection. | **trivial** — `$.makeArray(elem)[0]` → normalize input then `[0]`. |
| `getInputBasename` | `(elem) => string\|null` | `name` attr with `[...]` stripped. | **trivial** — `el.getAttribute('name')`. |
| `getInputPostVal` | `($input) => string\|string[]\|null` | POST value (unchecked checkbox/radio → null, non-`[]` multiselect → last, null select → ''). | **trivial** — read `type`/`checked`/`value`/`tagName`; `value` for multiselect needs `Array.from(select.selectedOptions)`. |
| `findInputs` | `(container) => Element[]` | `input,text,textarea,select,button` within. | **trivial** — `querySelectorAll`. (Note: `text` is not a real tag — legacy bug, preserve selector string but it matches nothing.) |
| `getPostData` | `(container) => Record<string,string>` | Serialize inputs to a flat POST map, handling `name[]` array indexing. | **trivial** once `findInputs`/`getInputPostVal` are ported. Pure logic otherwise. |
| `copyInputValues` | `(source, target) => void` | Copy values input-by-input, skipping file inputs. | **trivial.** |
| `handleActivatingKeypress` | `(event, callback) => void` | Space/Enter → preventDefault + callback. | **none** logic; **deprecate** (`activate` event supersedes it) — §4.D. |

### 4.C Constants & shared state (keep as-is on the namespace)

DOM objects `$win`/`$doc`/`$bod` and `$scrollContainer` are **jQuery collections** in legacy. Modern core replaces
them with native references and exposes both:

| Legacy | Modern core |
| --- | --- |
| `$win` (`$(window)`) | `win = window` (compat exposes `$win`). |
| `$doc` (`$(document)`) | `doc = document`. |
| `$bod` (`$(document.body)`) | `bod = document.body`. |
| `$scrollContainer` | `scrollContainer: EventTarget = window`. |

Plain constants (keep verbatim, **none**): all key codes (`BACKSPACE_KEY`…`META_KEY`), `JS_ARIA_CLASS`,
`JS_ARIA_TRUE_CLASS`, `JS_ARIA_FALSE_CLASS`, `PRIMARY_CLICK`, `SECONDARY_CLICK`, `X_AXIS`, `Y_AXIS`, `FX_DURATION`,
`TEXT_NODE`, `SHAKE_STEPS`, `SHAKE_STEP_DURATION`. Mutable flags: `activateEventsMuted`, `resizeEventsMuted`, `rtl`,
`ltr`. Internal memo fields `_isMobileBrowser`, `_isMobileOrTabletBrowser`, `_eventHandlers` (→ `ClassEventBus`
internals).

`rtl`/`ltr` derive from `document.body.classList.contains('rtl')` at init.

### 4.D Candidates for deprecation

Keep them working (compat) but mark `@deprecated` and prefer the alternative:

| Member | Reason / replacement |
| --- | --- |
| `log` | Already `@deprecated`. Use `console` directly. |
| `isArray` | Use `Array.isArray`. |
| `handleActivatingKeypress` | Already `@deprecated`. Use the `activate` event. |
| `isMobileBrowser` | UA sniffing; prefer feature/pointer media queries. Flag, don't remove yet (call sites exist). |
| `isJquery` | jQuery-only concept → compat layer, not core. |
| `Menu` (alias of `CustomSelect`), `ShortcutManager` (alias of `UiLayerManager`) | Already `@deprecated` aliases — compat-only. |
| `$win`/`$doc`/`$bod`/`$scrollContainer` (jQuery forms) | Native `win`/`doc`/`bod`/`scrollContainer` in core; jQuery forms are compat-only. |

### 4.E New core primitive required: focusable-element matcher

Several focus helpers depend on jQuery-UI's `:focusable`/`:focusable:visible`. The modern core must ship a native
equivalent, since this has no built-in DOM counterpart:

```ts
// utils/focusable.ts
export function isFocusable(el: Element): boolean;          // visible + not disabled + naturally/explicitly focusable
export function isKeyboardFocusable(el: Element): boolean;  // isFocusable && tabindex !== '-1'
export function getFocusableElements(container: Element): HTMLElement[];
export function isVisible(el: Element): boolean;            // offsetParent / getClientRects / visibility check
```

This is the single biggest reimplementation risk. The matcher must cover: links/areas with `href`, form controls not
`disabled`, `[contenteditable]`, `[tabindex]` (incl. negative for `isFocusable` but excluded for keyboard), and
visibility (`display:none`/`visibility:hidden`/zero-size). Recommend modeling on the well-known
jQuery-UI `:focusable` algorithm to preserve behavior.

---

## 5. Settings system

### 5.1 Legacy

```js
setSettings(settings, defaults) {
  const base = typeof this.settings === 'undefined' ? {} : this.settings;
  this.settings = $.extend({}, base, defaults, settings);
}
```

`$.extend({}, a, b, c)` is a **shallow** left-to-right merge into a fresh object: `c` wins over `b` wins over `a`.
Precedence: existing `this.settings` (lowest) < `defaults` < `settings` (highest).

### 5.2 Modern (no jQuery)

`$.extend` shallow is exactly `Object.assign` for plain objects. **Decision: shallow merge** (matching legacy; do
**not** silently deep-merge — call sites rely on shallow semantics, e.g. replacing a whole nested options object).

```ts
setSettings<S extends GarnishBaseSettings>(settings?: Partial<S>, defaults?: Partial<S>): void {
  const base = this.settings ?? {};
  this.settings = Object.assign({}, base, defaults, settings) as S;
}
```

Notes:

- `null`/`undefined` args are skipped by `Object.assign` (matches `$.extend` ignoring them).
- Legacy guards `typeof this.settings === 'undefined'`; modern uses `?? {}` (covers both `null` initial value and
  unset).
- A typed `defineSettings<S>(defaults: S)` helper is **recommended** so subclasses declare defaults once and get a
  typed `this.settings`. Optional sugar, not required for parity.

---

## 6. Proposed module layout & export surface

```
packages/craftcms-garnish/src/
  index.ts                 # public barrel; assembles the Garnish namespace object
  base.ts                  # abstract Base<S> class
  events.ts                # parseEvents/formatDomEvents, GarnishEvent, EventEmitter, ClassEventBus
  dom-listeners.ts         # DomListenerRegistry, ElementInput coercion
  custom-events/
    index.ts               # installActivate/installTextchange/installResize + shared ResizeObserver
    activate.ts
    textchange.ts
    resize.ts
  managers/
    esc-manager.ts         # EscManager (Base subclass)
    ui-layer-manager.ts    # UiLayerManager (Base subclass)
  utils/
    index.ts               # re-export
    dom.ts                 # coerceElement(s), hasAttr, getOffset, hitTest, isCursorOver, getElement, copyTextStyles
    focusable.ts           # isFocusable/isKeyboardFocusable/getFocusableElements/isVisible (§4.E)
    focus.ts               # focusIsInside, first/setFocusWithin, trap/releaseFocusWithin, getFocusedElement
    aria.ts                # addModalAttributes, ariaHide, hasJsAriaClass, isScriptOrStyleElement, modal-bg layer helpers
    forms.ts               # getInputBasename, getInputPostVal, findInputs, getPostData, copyInputValues
    animation.ts           # shake, scrollContainerToElement, raf/caf, prefersReducedMotion, getUserPreferredAnimationDuration
    env.ts                 # isMobileBrowser, isPrimaryClick, isCtrlKeyPressed, getBodyScrollTop
    misc.ts                # getDist, within, isString, isArray(dep), isTextNode, log(dep), handleActivatingKeypress(dep)
  constants.ts             # key codes, ARIA classes, axis/click/node/FX/shake constants
  types.ts                 # GarnishBaseSettings, ElementInput, shared types
  globals.ts               # win/doc/bod/scrollContainer refs, rtl/ltr, mute flags, shared ClassEventBus instance
```

### 6.1 Public exports (`index.ts`)

The core exports **named symbols** (tree-shakeable, the modern preference) **and** assembles a `Garnish` namespace
object that mirrors the legacy shape for incremental migration and for the compat layer to extend.

```ts
// Named exports (preferred for new code)
export { Base } from './base';
export type { GarnishBaseSettings } from './types';
export {
  EventEmitter, ClassEventBus, parseEvents, formatDomEvents,
  type GarnishEvent, type GarnishEventHandler,
} from './events';
export { DomListenerRegistry, type ElementInput, type DomListenerOptions } from './dom-listeners';
export { installActivate, installTextchange, installResize } from './custom-events';
export { EscManager } from './managers/esc-manager';
export { UiLayerManager } from './managers/ui-layer-manager';
export * from './utils';
export * from './constants';

// Namespace object (legacy-shaped; what compat re-exposes as window.Garnish)
export const Garnish = {
  // constants & globals
  ...constants, win, doc, bod, scrollContainer, rtl, ltr,
  activateEventsMuted: false, resizeEventsMuted: false,
  // classes
  Base, EscManager, UiLayerManager, /* + other modules as ported */
  // class-level event bus (bound to shared instance)
  on: (...a) => garnishClassBus.on(...a),
  off: (...a) => garnishClassBus.off(...a),
  once: (...a) => garnishClassBus.once(...a),
  // utilities (all of §4 that survive in core)
  getDist, within, hitTest, getOffset, /* ... */
  // managers are attached at init time:
  escManager: undefined as EscManager | undefined,
  uiLayerManager: undefined as UiLayerManager | undefined,
};

export function initGarnish(): typeof Garnish {
  Garnish.escManager = new EscManager();
  Garnish.uiLayerManager = new UiLayerManager();
  return Garnish;
}

export default Garnish;
```

### 6.2 Manager singletons

Legacy attaches `Garnish.escManager` / `Garnish.uiLayerManager` during init (other modules read them, e.g.
`hideModalBackgroundLayers` uses `Garnish.uiLayerManager`). Modern core exposes `initGarnish()` to instantiate them
once and assign onto the namespace, instead of the implicit global-singleton-on-import side effect. The compat layer
calls `initGarnish()` and binds the result to `window.Garnish` (guarded like legacy's
`if (typeof window.Garnish === 'undefined')`).

### 6.3 What the core deliberately omits

- No `window.Garnish` assignment in core (compat does it).
- No `$.fn.activate/textchange/resize` chaining sugar (jQuery-only; compat re-adds).
- No `$.event.special` (replaced by §3 installers).
- No `isJquery` (compat).
- Velocity.js is removed entirely; animation helpers use WAAPI/rAF.

---

## 7. Compatibility wart register (for the compat layer)

Behaviors the modern core preserves *as quirks* so the compat layer doesn't have to special-case them:

1. Event split inconsistency: pub/sub splits on space, `addListener` on comma (§2.1).
2. `trigger` event-object key precedence: `{data} <- triggerData <- {type,target}` (§2.3).
3. `findInputs` selector includes the bogus `text` tag (§4.B) — preserved verbatim.
4. `getInputPostVal` returns `''` (not `null`) for a null-valued `<select>`.
5. `_namespace` random scheme kept (UUID with fallback) for parity/debugging.
6. `once` implemented as wrapper-closure so `off(events, handler)` removes it (§2.3).
7. `Garnish.on/once` `console.warn` on undefined target (§2.4).

---

## 8. Open decisions flagged for the team

- **`isMobileBrowser`**: keep UA-sniffing port verbatim, or replace with pointer/media-query detection? Recommend keep
  for now (call-site parity), schedule replacement.
- **`getFocusedElement` return type**: legacy returns a jQuery collection; modern returns `Element | null`. Confirm no
  core call site relies on collection methods (compat returns `$(...)`).
- **Focusable matcher fidelity** (§4.E): must match jQuery-UI `:focusable` closely; budget test coverage here — it
  underpins focus traps and modal accessibility.
- **`shake`/`scrollContainerToElement`** animation backend: WAAPI vs rAF. Recommend WAAPI (`element.animate`) for
  shake, manual `scrollTop` tween (or `scrollIntoView({behavior})`) for scroll, both gated by
  `prefersReducedMotion`.
