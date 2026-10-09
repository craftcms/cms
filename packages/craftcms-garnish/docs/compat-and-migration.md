# Compat & Migration

`@craftcms/garnish` ships **two surfaces from one package**:

- The **modern** entry (`@craftcms/garnish`) — jQuery-free ES classes, native events,
  and tree-shakeable utilities. This is the surface for new code.
- An opt-in **compat** entry (`@craftcms/garnish/compat`) — restores the legacy
  `Garnish.Base.extend()` / `init()` / `this.base()` / jQuery authoring contract and a
  `window.Garnish` global on top of the same modern core, so existing Craft plugins can
  adopt the package with (near) zero changes and migrate incrementally.

This guide covers adopting the compat layer in an existing plugin and migrating off it
class by class. For the symbols each entry exports, see the
[API reference](api-reference.md); for how compat is implemented, see the
[architecture doc](architecture.md#compatibility-layer).

---

## Adding the compat layer

Add a single **side-effecting** import at your bundle entry point:

```ts
import '@craftcms/garnish/compat';

// `window.Garnish` is now populated. Existing code keeps working unchanged:
Craft.MyModal = Garnish.Modal.extend({
  init(container) {
    this.base(container, {closeOtherModals: true}); // calls Modal's constructor
  },
  onShow() {
    this.base(); // calls Modal.prototype.onShow
    // ...custom behavior...
  },
});

new Craft.MyModal($('#my-modal')); // jQuery collection args are unwrapped for you
```

That import installs `window.Garnish`, guarded by
`if (typeof window.Garnish === 'undefined')` so it never clobbers an existing
legacy-bundle global.

### jQuery requirement

jQuery is an **optional peer dependency** — required **only** by the compat entry, and
only at runtime. The modern `.` entry never imports jQuery, never reads
`window.jQuery`/`$`, and never assigns `window.Garnish`.

jQuery must be present on the global scope for the jQuery-shaped parts of compat:
`$container`-style constructor args, the `$win`/`$doc`/`$bod`/`$scrollContainer`
globals, `isJquery`, and the `$.fn.activate/textchange/resize` chaining sugar. If a
jQuery-only feature is used while jQuery is absent, the layer throws a clear,
actionable error; everything that doesn't need jQuery still works.

### Programmatic (non-auto-install) use

To build the namespace without the side effect — or to wrap a single class — use the
named exports instead of the bare import:

```ts
import {installGarnishCompat, compatify} from '@craftcms/garnish/compat';
import {Modal} from '@craftcms/garnish';

const Garnish = installGarnishCompat(); // build + assign window.Garnish (idempotent)
const LegacyModal = compatify(Modal);   // one legacy-shaped class, without the global
```

`buildGarnishCompat()` builds the namespace **without** touching `window` at all.

---

## What compat restores

| Legacy affordance | How compat provides it |
| --- | --- |
| `window.Garnish` global | Built by running `compatify` over every modern class, then assigned under the legacy `typeof window.Garnish === 'undefined'` guard. |
| `Class.extend(instance, static)` | Returns a real subclass — `instance` members on the prototype, `static` on the constructor — backed by a genuine prototype chain. |
| `init()` as the constructor | The compat subclass's `constructor` calls `super(...)` then `this.init(...)` if you supplied one. You keep writing `init`. |
| `this.base(...)` super-dispatch | Detected from method source and bound to the overridden ancestor on entry, restored on exit, so nested/re-entrant super-calls stay correct. |
| jQuery-collection constructor args | Coerced to the native `Element`/`EventTarget` the modern class expects. `$`-prefixed props (`this.$container`, `this.$trigger`, …) are exposed as jQuery wrappers via getters. |
| `isJquery`, `$win`/`$doc`/`$bod`/`$scrollContainer` | Restored on the namespace; a jQuery-wrapped `getFocusedElement()`; the `$.fn.activate/textchange/resize` sugar routes to the core installers. |
| Deprecated aliases | `ShortcutManager` → `UiLayerManager`; `Menu` → `CustomSelect` (when ported). |

Namespaced events need no special handling: the modern emitter already preserves the
legacy event-string grammar and trigger-object precedence, so `on('show.myns', fn)` /
`off('.myns')` and `addListener(el, 'click.foo', fn)` behave identically on both
surfaces.

---

## Migrating off compat

The compat layer is **opt-in and tree-shakeable** — you remove it by removing its
import. Migrate one class at a time; modern and compat code coexist in the same bundle
during the transition.

**1. Convert a subclass.** Turn a `Garnish.X.extend({init() {…}})` subclass into a
native `class extends X`:

```ts
// before (compat)
Craft.MyModal = Garnish.Modal.extend({
  init(container) {
    this.base(container, {closeOtherModals: true});
  },
  onShow() {
    this.base();
    // ...custom behavior...
  },
});

// after (modern)
import {Modal, type ModalSettings} from '@craftcms/garnish';

class MyModal extends Modal {
  constructor(container: Element, settings?: Partial<ModalSettings>) {
    super(container, {closeOtherModals: true, ...settings});
    // ...custom behavior...
  }

  override onShow(): void {
    super.onShow();
    // ...custom behavior...
  }
}
```

Replace `init` with a real `constructor`, `this.base(...)` with `super.method(...)`,
and jQuery-collection args with native elements (`$('#x')` → `document.querySelector('#x')`,
and read `el`, not `$el[0]`). `this.base` is a compat-only affordance; TypeScript
migrators should always use `super`.

**2. Drop the import.** Once nothing depends on `window.Garnish` or the legacy
affordances, **delete the `import '@craftcms/garnish/compat'` line.** Tree-shaking then
drops all of the compat code, the `window.Garnish` global, and the jQuery peer
requirement, leaving the clean modern surface with zero legacy weight.

---

## Notes for migrators

A few behavioral differences are worth knowing when you move call sites onto the modern
surface:

- **`$`-prefixed properties are raw elements.** On the modern classes,
  `modal.$container`, `dragDrop.$activeDropTarget`, `hud.$main`, etc. are
  `HTMLElement | null`, **not** jQuery collections. Drop the trailing `[0]`/`.get(0)`.
- **`once` is removed by its wrapper.** Because `once` is a self-removing wrapper,
  `off(events, originalHandler)` does **not** remove a pending `once` — remove it by
  type/namespace instead.
- **`getFocusedElement()` returns an `Element`.** The modern helper returns
  `Element | null`; compat wraps it with `$()` for legacy callers.
- **Some helpers are deprecated but kept.** `log` (use `console`), `isArray` (use
  `Array.isArray`), `handleActivatingKeypress` (use the `activate` event), and
  `isMobileBrowser` (prefer feature queries) still work but are flagged.

The [architecture doc](architecture.md#compatibility-wart-register) lists the full set
of intentionally-preserved legacy quirks.
