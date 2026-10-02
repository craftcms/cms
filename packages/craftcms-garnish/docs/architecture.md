# Architecture

The Garnish core (`@craftcms/garnish`) is a jQuery-free, ESM/TypeScript foundation that every other Garnish module builds on: a class system, a dual event system, a utility surface, a settings system, and the module layout that ties them together. It ships as two layers from one package — a clean **modern core** built on native `class extends`, native DOM APIs, and the Web Animations API, and a separate, opt-in **compatibility layer** (`@craftcms/garnish/compat`) that mechanically wraps the modern classes to restore the legacy authoring contract (`Garnish.Base.extend(...)`, `this.base()`, jQuery-collection arguments, `window.Garnish`). New Craft features target the modern surface directly; legacy modules keep working through compat until they migrate at their own pace.

This document covers the modern core and explains how the compat layer bridges back to legacy. For exact signatures of every exported symbol, see the [API reference](api-reference.md); for adopting the package in an existing plugin, see [compat &amp; migration](compat-and-migration.md).

---

## Class system

The core uses native ES classes. `Base<S>` is an abstract base that no code instantiates directly; subclasses extend it and call `super()` from the `constructor`. There is no `init()` trampoline and no `this.base()` — subclasses do their own setup after `super()` and call `super.method()` to reach an overridden ancestor.

This is a deliberate departure from the legacy core, which used Dean Edwards `Base.js` (the `.extend(instance, static)` prototype system). In the legacy world, `Garnish.Base`'s constructor initialized `_eventHandlers`, `_namespace`, and `_listeners`, then called `this.init.apply(this, arguments)`, so subclasses overrode `init`, never `constructor`. The modern core drops that indirection entirely; the legacy contract is reconstructed only in the compat layer (see *Compatibility layer*).

Settings are generic (`Base<S>`) so subclasses get a typed `this.settings`. Getters that were descriptor-copied in the legacy code (for example `UiLayerManager.layer` / `currentLayer`) become native `get` accessors — a one-to-one move. `Base` owns the disable gate (`disable()` / `enable()` / `get disabled`) and `destroy()`, which fires a `destroy` event, tears down all tracked DOM listeners, and clears the pub/sub registry.

A constraint the core honors for compat's sake: constructors stay plain and method names stay stable, so the compat layer can wrap them mechanically. No core method depends on an `init()` indirection existing.

---

## Event system

Two distinct event systems coexist, exactly as they did in the legacy core, expressed here as one coherent emitter design. Both are reproduced without jQuery:

- **Object pub/sub** — `Base.prototype.on/off/once/trigger`. An in-memory observer registry, *not* DOM events.
- **Class-level pub/sub** — `Garnish.on/off/once`. Handlers registered against a class are dispatched, on any `trigger`, to instances that are `instanceof` that class.
- **Namespaced DOM listeners** — `Base.prototype.addListener/removeListener/removeAllListeners`. Native event binding with per-instance namespacing, delegation, and a disable gate.

### Event-string grammar

The legacy event-string grammar is preserved verbatim so call sites are unchanged:

- `on` / `off` / `trigger` strings split on **spaces** into multiple events.
- Each event splits on its **first** `.` into `[type, namespace]` (`'click.foo'` → type `click`, namespace `foo`).
- `addListener` additionally splits its events argument on **commas**, then appends the instance namespace to each before binding.

`parseEvents(events, splitOn)` is the canonical parser (`splitOn` is a space for pub/sub, a comma for `addListener`); `formatDomEvents` appends the instance namespace for DOM binding. An empty type with a namespace is the valid "remove everything in this namespace" form for `off`.

The space-vs-comma split inconsistency is intentional and preserved on both paths — it is a documented compatibility wart so the compat layer need not special-case it. `parseEvents` is also re-exported under the legacy name `_normalizeEvents`.

### Object pub/sub — `EventEmitter`

`EventEmitter` backs `Base.on/off/once/trigger`. Each `Base` instance owns one emitter, and the emitter holds `this` as the event target.

Semantics that match the legacy behavior exactly:

- **Argument overload:** in `on`/`once`, if the second argument is a function it is the handler and per-registration `data` defaults to `{}`; otherwise the second argument is `data` and the third is the handler.
- **Trigger precedence:** for each matching handler the event object is built as `{data: registration.data}` ← trigger-time `data` ← `{type, target}`. That is, trigger-time `data` keys win over per-registration `data`, and `type`/`target` always win last. This mirrors the legacy `$.extend({data}, data, ev)` order and is observable — some call sites pass `target` overrides in `data` that the final object is expected to override.
- **`off` matching:** a registration matches when the type matches **and** (the parsed namespace is empty **or** namespaces match) **and** (no handler was passed **or** the handler is `===`). Matches are removed by iterating backwards and splicing, as the legacy code did.
- **`once`:** implemented as a self-removing wrapper closure, not a boolean flag. This guarantees identical `off` behavior — see the *as-built decisions* below for the consequence.

Beyond the legacy behavior, the emitter also supports a **wildcard** registration: `on('*', handler)` matches every `trigger`, with the handler still receiving the event's real `type` (built via `buildEvent`). This lets a wrapper forward all of a controller's events generically — e.g. the control panel's `ControllerElement` custom-element base re-emits them as native, bubbling DOM `CustomEvent`s, so consumers can `addEventListener` without referencing the controller class.

### Class-level pub/sub — `ClassEventBus`

`ClassEventBus` backs `Garnish.on/off/once`. A single shared instance, `garnishClassBus`, lives in the core's globals module; the `Garnish` namespace object proxies `on/off/once` to it.

`Base.trigger(type, data)` runs two passes, like the legacy code: first its own `_emitter.trigger`, then `garnishClassBus.dispatch(this, type, data)`. Dispatch walks the class-level registrations and invokes each whose target the triggering instance is an `instanceof`. Registering against an `undefined` target emits a `console.warn` (a preserved wart) rather than throwing.

### DOM listeners — `DomListenerRegistry`

`DomListenerRegistry` replaces `addListener`/`removeListener`/`removeAllListeners`. The legacy versions leaned on jQuery for element coercion, namespaced binding, delegated binding, and the disable gate. The modern registry does all of this with native APIs.

Because native `addEventListener` has no concept of namespaces, the registry stores `{element, type, namespace, wrappedHandler, capture}` tuples and uses that array — not the namespace string — as the real removal mechanism, replicating jQuery's namespaced `.off`. Delegation is implemented with `event.target.closest(selector)` plus a `container.contains` check inside the wrapped handler. Handlers are short-circuited while the host's `disabled` flag is true. The registry tracks every binding so `destroy` can remove them all.

The registry's constructor takes any host exposing a readonly `disabled` getter — `Base` satisfies this structurally. `Base`'s `addListener`/`removeListener`/`removeAllListeners` are thin, signature-compatible wrappers over it. Legacy `addListener` quirks are reproduced: it bails silently when no elements resolve; it maps `(elem, events, func)` vs `(elem, events, data, func)` by checking whether the third argument is a plain object; and the handler may be a **method-name string** resolved against the host (`UiLayerManager`, for instance, binds `'triggerShortcut'` this way).

Element inputs are normalized by `coerceElements()` in the DOM utilities: a string becomes a `querySelectorAll`, a node or `window` becomes a single-element array, arrays and `NodeList`s are flattened, and `null`/`undefined` becomes an empty array.

### Per-instance namespace

Each `Base` carries a `_namespace` string (`.Garnish<uuid>`). In the legacy code this string scoped jQuery DOM listeners; here it is bookkeeping only — generated with `crypto.randomUUID()` (falling back to a `Math.random` scheme), kept for compat parity and debugging. The registry's tuple array, not the namespace, is what actually drives namespaced removal.

---

## Custom DOM events

Three custom events are owned by the core because other modules and the legacy CP depend on them heavily — `activate` especially:

- **`activate`** — `mousedown` on a `<button>`/`role=button` calls `preventDefault` (no focus-on-click); `click` and `keydown` of Space/Enter dispatch an `activate` event unless the element is `.disabled` or `Garnish.activateEventsMuted`. Ctrl/⌘-click on an `<a href>` (non-`#`, non-empty) is ignored. The installer sets `tabindex="0"` on non-disabled, non-body elements and removes it on disabled elements (unless `.read-only`). Bubbled `keydown` events are ignored (it only acts when `this === target`).
- **`textchange`** — stores the last value and dispatches a `textchange` event only when the value actually changes, with an optional debounce via a `delay` option.
- **`resize`** — `window` uses the native `resize` event; other elements share one lazily-created `ResizeObserver` keyed on stored `{width, height}`, dispatching only when dimensions change and `Garnish.resizeEventsMuted` is false.

The legacy core wired these through jQuery's `$.event.special` and added `$.fn.activate/textchange/resize` chaining sugar. The modern core cannot use `$.event.special`, so it provides **installers** — `installActivate`, `installTextchange`, `installResize` — that wire native listeners on a real `EventTarget` and re-dispatch a synthetic `CustomEvent`. Each installer returns a ref-counted disposer and is idempotent per element/type. `DomListenerRegistry.add` recognizes these synthetic types and routes `addListener(el, 'activate'|'textchange'|'resize', fn)` through the matching installer automatically, so binding them works like any other event.

The shared `Garnish.activateEventsMuted` / `Garnish.resizeEventsMuted` flags and `muteResizeEvents(cb)` live on the namespace object.

---

## Utilities

The core ports the full legacy `Garnish` utility surface (~40 members) to native JS, grouped into focused modules under `utils/`. The shape of that surface is the API reference's job; this section covers the categories and the decisions worth knowing.

**Pure helpers** carry over unchanged: geometry (`getDist`, `within`), type checks (`isString`, `isTextNode`), input classification (`isPrimaryClick`, `isCtrlKeyPressed`), motion preferences (`prefersReducedMotion`, `getUserPreferredAnimationDuration`), and the RAF shims (now plain re-exports of native `requestAnimationFrame`/`cancelAnimationFrame`, since vendor prefixes are long dead).

**DOM helpers** are rewritten without jQuery but behave identically: `hasAttr`, `getOffset`, `hitTest`, `isCursorOver`, `getElement`, `copyTextStyles`, the ARIA helpers (`addModalAttributes`, `ariaHide`, `hasJsAriaClass`, `isScriptOrStyleElement`), and the modal background-layer helpers (`hideModalBackgroundLayers`, `resetModalBackgroundLayerVisibility`), which iterate `document.body.children` and use `closest`/`querySelectorAll` instead of jQuery selectors.

**Form helpers** (`getInputBasename`, `getInputPostVal`, `findInputs`, `getPostData`, `copyInputValues`) read native `type`/`checked`/`value`/`tagName`; multi-selects read `select.selectedOptions`.

**Animation helpers** drop Velocity. `shake` uses the Web Animations API (`element.animate`); `scrollContainerToElement` uses instant `scrollTop`/`window.scrollTo`. Both respect `prefersReducedMotion`.

**Globals and constants** stay on the namespace. Native `win`/`doc`/`bod` replace the jQuery `$win`/`$doc`/`$bod` collections, and `scrollContainer` defaults to `window`. Key codes, ARIA class names, axis/click/node/FX/shake constants, and the mutable flags (`activateEventsMuted`, `resizeEventsMuted`, `rtl`, `ltr`) are exposed as before; `rtl`/`ltr` derive from `document.body.classList` at init.

A handful of members are kept working but marked `@deprecated` in favor of a native alternative: `log` (use `console`), `isArray` (use `Array.isArray`), `handleActivatingKeypress` (use the `activate` event), and the jQuery-shaped globals (`$win`/`$doc`/`$bod`/`$scrollContainer`, restored in compat). `isMobileBrowser` still UA-sniffs and is flagged for eventual replacement by pointer/media-query detection, but is kept for call-site parity.

### Focusable-element matcher

Several focus helpers depended on jQuery-UI's `:focusable` / `:focusable:visible`, which has no native DOM equivalent, so the core ships its own matcher in `utils/focusable.ts`: `isFocusable`, `isKeyboardFocusable`, `getFocusableElements`, and `isVisible`. It is modeled on the jQuery-UI `:focusable` algorithm and covers `<a>`/`<area>` with `href`, non-disabled form controls, anything with a numeric `tabindex` (negative tabindex counts as focusable but not keyboard-focusable), `[contenteditable]`, and a visibility gate (`offsetWidth`/`offsetHeight` or `getClientRects().length`, plus a `visibility: hidden/collapse` check on the element and its ancestors). `getFocusableElements` excludes the container itself, matching the legacy `.find()` scope.

This matcher underpins focus traps and modal accessibility, so it carries a heavy test budget. One testing wrinkle: happy-dom has no layout, so `offsetWidth`/`offsetHeight` are always 0; tests stub `getClientRects` to assert focusability. Real browsers need no such stub.

The focus helpers built on the matcher (`focusIsInside`, `firstFocusableElement`, `getKeyboardFocusableElements`, `trapFocusWithin` / `releaseFocusWithin`, `setFocusWithin`, `getFocusedElement`) are reimplemented natively. `setFocusWithin` preserves the `.field:visible` heuristic (cms#15245) verbatim. `trapFocusWithin`/`releaseFocusWithin` use the matcher together with a `focus-trap` namespace on the `DomListenerRegistry`.

---

## Settings system

Settings use a shallow, left-to-right merge into a fresh object, matching the legacy `$.extend({}, base, defaults, settings)` precisely:

```ts
setSettings(settings?, defaults?) {
  this.settings = Object.assign({}, this.settings ?? {}, defaults, settings);
}
```

Precedence runs existing `this.settings` (lowest) → `defaults` → `settings` (highest), and `null`/`undefined` arguments are skipped (as `Object.assign` and `$.extend` both do). The merge is intentionally **shallow** — call sites rely on a passed nested options object replacing the whole prior object, so the core does not silently deep-merge. A typed `defineSettings<S>` helper lets subclasses declare defaults once and get a typed `this.settings`; it is optional sugar, not required for parity.

---

## Module layout & exports

The core is organized into focused modules:

```
packages/craftcms-garnish/src/
  index.ts            # public barrel; assembles the Garnish namespace object + initGarnish()
  base.ts             # abstract Base<S>
  events.ts           # parseEvents/formatDomEvents, GarnishEvent, EventEmitter, ClassEventBus
  dom-listeners.ts    # DomListenerRegistry
  custom-events/      # installActivate/installTextchange/installResize + shared ResizeObserver
  managers/
    esc-manager.ts
    ui-layer-manager.ts
    registry.ts       # manager-singleton registry (see below)
  utils/              # dom, focusable, focus, aria, forms, animation, env, misc
  icons/
    resize-handle.ts  # ResizeHandle SVG export
  constants.ts        # key codes, ARIA classes, axis/click/node/FX/shake constants
  types.ts            # GarnishBaseSettings, ElementInput, shared types
  globals.ts          # win/doc/bod/scrollContainer, rtl/ltr, mute flags, garnishClassBus
```

The core exports **named symbols** (tree-shakeable, the modern preference) **and** assembles a legacy-shaped `Garnish` namespace object for incremental migration and for the compat layer to extend. The namespace spreads the constants, exposes `win`/`doc`/`bod`, the classes, the class-level bus (`on/off/once` proxied to `garnishClassBus`), the `_normalizeEvents` alias, every surviving utility, `muteResizeEvents`, `ResizeHandle`, and empty `escManager`/`uiLayerManager` slots. Mutable flags are exposed through get/set accessors backed by the globals object so flag mutations stay live across the named and namespace surfaces.

`initGarnish()` instantiates the two manager singletons (`EscManager`, `UiLayerManager`) once, idempotently, and assigns them onto the namespace — replacing the legacy implicit global-singleton-on-import side effect. The core deliberately **never** assigns `window.Garnish`; that is the compat layer's job. It also omits the `$.fn` chaining sugar, `$.event.special`, `isJquery`, and Velocity — all either replaced by installers or restored in compat.

A small **manager-singleton registry** (`managers/registry.ts`) breaks the circular import between `utils/aria.ts` (which needs the active UI layer manager for background-layer hiding) and `managers/ui-layer-manager.ts`: `initGarnish()` calls `setUiLayerManager()`, and the ARIA helpers read the manager through the registry rather than importing it directly.

---

## How key behaviors work

Several behaviors deserve a closer look because they differ from a naïve port or carry a subtle consequence:

- **`once` is removed only by its wrapper.** Because `once` is implemented as a self-removing wrapper closure (for `off` parity), calling `off(events, originalHandler)` does **not** remove a pending `once` registration — only the internal wrapper matches. Remove a pending `once` by type/namespace instead.
- **DOM events are the native event, augmented.** For DOM listeners the real `Event` is passed straight through, and only the *missing* Garnish fields are added via `Object.defineProperty` — `data` and `garnishTarget` (the delegated match). `type` and `target` are never written, because they are read-only native getters and assigning to them throws in both happy-dom and real browsers. The delegated target lands on `garnishTarget` rather than overwriting the read-only native `currentTarget`/`target`. Pub/sub events, by contrast, are plain objects.
- **`getOffset`/`hitTest` use live geometry.** They drop the legacy instance-cache fields (`_offset`, `_$elem`, `_x1`…) and read `getBoundingClientRect()` + `scrollX`/`scrollY` on each call. Behaviorally equivalent, simpler, and free of stale-cache bugs.
- **`shake` is a single WAAPI ramp.** Instead of the legacy 11× `setTimeout` + Velocity loop, `shake` runs one keyframe ramp through `element.animate`, and is a no-op under `prefersReducedMotion`. `scrollContainerToElement` scrolls instantly (no smooth tween, since Velocity is gone).
- **`isPrimaryClick` prefers `button`.** It reads `ev.button === 0` for native events but still honors a legacy `ev.which === 1` when present.
- **`getFocusedElement` returns an element.** It returns `Element | null` (`document.activeElement`), not the jQuery collection the legacy helper returned; compat wraps it with `$()` for legacy callers.
- **`findInputs` keeps the bogus `text` tag.** Its selector still lists a `text` tag that matches no real element — a preserved legacy quirk.
- **`getInputPostVal` returns `''` for a null-valued `<select>`** (not `null`), and reads `select.selectedOptions` for multi-selects — both matching legacy output.

---

## Compatibility layer

The compatibility layer (`@craftcms/garnish/compat`) restores the entire legacy authoring contract by mechanically wrapping the modern classes. It is **opt-in**: importing it has side effects (it populates `window.Garnish` and wraps every class) and pulls in jQuery as a peer dependency; not importing it gives the clean modern surface with zero legacy weight. A legacy module runs unchanged with a single `import '@craftcms/garnish/compat'` at its bundle entry; a module migrates by switching to named modern imports and `class extends` at its own pace; a fully modern bundle drops the import and tree-shakes the compat code (and `window.Garnish`) away.

The heart of the layer is `compatify(ModernClass)` — a higher-order function that takes a modern ES class and returns a legacy-shaped constructor supporting `.extend(instance, static)`. The modern core's plain constructors and stable method names are precisely what let this be mechanical. It restores each legacy behavior as follows:

- **`.extend(instance, static)` → a subclass.** The returned constructor exposes a static `extend` that builds a real subclass of the modern class — `instance` members copied onto the prototype, `static` members onto the constructor — reproducing Dean Edwards `Base.js` semantics but backed by a genuine prototype chain.
- **`init` → constructor.** The compat subclass defines a `constructor(...args)` that calls `super(...args)` and then, if the `instance` object supplied an `init`, invokes `this.init(...args)`. Authors keep writing `init` and never touch `constructor`.
- **`this.base(...)` synthesis.** This is the subtle part. As in the legacy `Base.js`, the wrapper detects `/\bbase\b/` in a method's source and, on entry, sets `this.base` to the prototype method being overridden (found by walking the modern prototype chain), bound to `this`, then restores the previous `this.base` on exit via try/finally so nested super-calls remain correct. The save/restore dance is copied verbatim because it is observable and re-entrant. TypeScript-authored migrators should use `super.method()` instead; `this.base` is a compat-only affordance.
- **jQuery-collection arguments.** Modern constructors accept `Element | EventTarget | string | null`. Compat coerces incoming jQuery collections or selector strings to the native input the modern class expects, and exposes legacy `$`-prefixed properties (`this.$container`, `this.$trigger`, `this.$shade`) as jQuery wrappers around the modern native references via getters. (The `UiLayerManager` layer's `$container`, for example, is a native `Element` in the core; compat wraps it for consumers that call `.get(0)`.)
- **`window.Garnish`.** Compat builds the legacy global by running `compatify` over each class in the modern `Garnish` namespace, re-adding the jQuery-only members (`$win`/`$doc`/`$bod`/`$scrollContainer`, `isJquery`, the `$.fn.activate/textchange/resize` chaining sugar, the deprecated `Menu`/`ShortcutManager` aliases), calling `initGarnish()` to attach the manager singletons, and assigning the result under the same guard the legacy code uses (`if (typeof window.Garnish === 'undefined')`).
- **`isJquery`.** Dropped from core, restored in compat as `(v) => v instanceof jQuery`.
- **Namespaced events.** Nothing special is needed: the modern emitter already preserves the legacy event grammar and trigger-object precedence, so `on('show.myns', fn)` / `off('.myns')` and `addListener(el, 'click.foo', fn)` behave identically. Compat only adds the jQuery `$.event.special` chaining sugar (`$el.activate(fn)`), routing it to the core installers.

jQuery is a peer dependency of the `./compat` entry only; the modern entry stays jQuery-free.

---

## Compatibility wart register

The modern core preserves a handful of legacy behaviors *as quirks*, on purpose, so the compat layer never has to special-case them:

1. Event split inconsistency — pub/sub splits on spaces, `addListener` on commas.
2. `trigger` event-object precedence — `{data}` ← trigger data ← `{type, target}`.
3. `findInputs` includes the bogus `text` tag, preserved verbatim.
4. `getInputPostVal` returns `''` (not `null`) for a null-valued `<select>`.
5. `_namespace` keeps a random scheme (UUID with `Math.random` fallback) for parity/debugging.
6. `once` is a wrapper closure, so `off(events, handler)` does not remove it — remove by type/namespace.
7. `Garnish.on`/`once` emit a `console.warn` on an undefined target rather than throwing.
