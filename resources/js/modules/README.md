# CP JavaScript modules

Home for Control-Panel behavior ported out of the legacy jQuery bundle
(`packages/craftcms-legacy/cp/src/js/*.js`) into modern TypeScript. Each module
is a self-contained folder that follows one shared pattern so the next port
looks like the last one.

Reference implementations, simplest to richest:
[`sortable-checkbox-select`](./sortable-checkbox-select/),
[`listbox`](./listbox/),
[`grouped-entry-type-manager`](./grouped-entry-type-manager/),
[`component-select`](./component-select/).

## The port pattern

A ported module is a folder of (usually) five files:

| File | Role |
| --- | --- |
| `<name>.ts` | **The logic class.** A plain class extending `@craftcms/garnish` `Base`. This is the port of the legacy `Craft.Thing` — all behavior lives here. Constructed with `(container, settings)`; no custom-element or attribute concerns. |
| `<name>.ce.ts` | **The custom element.** A thin `ControllerElement` subclass (`resources/js/common/web-components/`) that boots the logic class around the server-rendered markup it wraps, and tears it down on disconnect. `.ce` = "custom element". |
| `support.ts` | **The instance registry.** A module-scoped `WeakMap<Element, Thing>` replacing the legacy jQuery `$el.data('thing')` back-reference, so other code can find the instance for an element. |
| `index.ts` | **The shim + barrel.** Registers the element (`defineElement`), assigns the legacy `window.Craft.*` global (or deliberately doesn't — see below), and re-exports the class, element, types, and registry. This is the file `cp.ts` / `legacy.ts` imports. |
| `README.md` | What legacy file it replaces, what changed, what stayed jQuery, and any deferred work. Mirror the existing ones. |

### The logic class (`<name>.ts`)

- `export class Thing extends Base<ThingSettings>`. Setup lives in an `init()`
  method invoked from the constructor **only for the leaf class** via a
  `new.target` guard — the construction contract shared by every port.
- Provide a `destroy()` that undoes everything `init()` wired (listeners,
  `DragSort`/observer teardown), then calls the `Base` teardown. `ControllerElement`
  calls it on disconnect.
- Register the instance in the `support.ts` WeakMap keyed by its container.
- **Events:** use garnish pub/sub — `this.trigger('change')`. `ControllerElement`
  re-emits every triggered event as a **bubbling DOM `CustomEvent`** on the
  element (name preserved, garnish payload on `detail`), so consumers listen with
  plain `addEventListener` and never import the class. Reserve a native
  `dispatchEvent` only for events whose `detail` an ancestor mutates
  synchronously (garnish's async-ish delivery won't do).
- **What stays jQuery:** these classes are mostly orchestrators of still-jQuery
  Craft/Garnish widgets, so the `Craft` / `$` / `Garnish` page globals survive at
  those seams (`declare const Craft: any`). Port the class, not the whole
  dependency tree — document the remaining seams in the README.

### The custom element (`<name>.ce.ts`)

`export default class CraftThing extends ControllerElement<Thing>`. Provide:

- `rootSelector` — a CSS selector for the server-rendered child that signals the
  markup is parsed (boot retries on `requestAnimationFrame` until it resolves, so
  it survives both initial HTML parse and AJAX/Inertia-injected fragments).
- `create(root)` — parse the element's attributes into the settings object and
  return `new Thing(this, settings)`. Attribute parsing replaces the legacy
  `{% js %}` settings blob.

Most ports keep the element a **dumb boot/teardown wrapper** and expose the
instance's API through the `support.ts` WeakMap (consumers do
`closestRegistered(el, thingData)` from `@craftcms/garnish`). Use a **forwarding
facade** — the element re-exposing the instance's methods — only when the
element itself is the documented public API and consumers set boot-independent
state on it (e.g. `component-select`, whose `getInputValue` hook may be assigned
before the instance has booted; there the element owns the hook and the class
reads it back through a settings callback).

### The shim (`index.ts`)

- `defineElement('craft-thing', CraftThing)` registers the element (guarded, so
  double-import is safe).
- Assign the legacy global so any remaining `new Craft.Thing(...)` boots keep
  working: `window.Craft.Thing = Thing`. Assign the **plain modern ES class** —
  **no `compatify`** — unless something still subclasses it via the legacy
  `Garnish.Base.extend()` (nothing in core does). Re-attach static members the
  legacy bundle exposed (`Thing.Item`, `Thing.Group`).
- **Sometimes there is deliberately no global**, when the legacy class must
  co-exist: `component-select` assigns nothing, because the legacy
  `Craft.ComponentSelectInput` still ships for not-yet-migrated Twig surfaces and
  overwriting it would break them. Say so in a comment and the README — the
  "shim" is then the intentional absence of one.

## Wiring it up

1. Import the module for its side effects from the right entry point —
   `resources/js/cp.ts` (Inertia/modern surfaces) and/or `resources/js/legacy.ts`
   (legacy Twig surfaces). Importing `index.ts` is what registers the element and
   assigns the global.
2. Update the Twig/PHP that rendered the legacy markup to emit `<craft-thing>`
   (with settings as attributes) instead of a manual `new Craft.Thing(...)`
   `{% js %}` boot.
3. Delete the legacy `cp/src/js/Thing.js`, remove its `import` from
   `packages/craftcms-legacy/cp/src/Craft.js`, and **rebuild the legacy bundle**
   (`cd packages/craftcms-legacy && pnpm run build`) — confirm the built
   `cms-assets/resources/legacy/cp/dist/cp.js` no longer references it.

## Verification

- `vp check` on the touched files.
- Behavior is verified live against the dev install (the CP), since these manage
  real server-rendered markup — typecheck/lint alone can't catch a broken boot.

## Gotcha: `@craftcms/garnish` / `@craftcms/cp` are consumed two ways

Both packages ship a `"development"` export condition pointing at their `src/`.
The **Vite dev server** activates it, so the running app reads their **source** —
edits under `packages/craftcms-garnish/src` are live with no rebuild. But
**`vue-tsc` typecheck** resolves via the `types` condition to the built
`dist/*.d.ts` (the app tsconfig has no `customConditions`, and adding one drags
in `@craftcms/cp` source, which relies on that package's own path aliases). So
after editing a package's `src`, **rebuild that package's `dist`**
(`pnpm run build` in the package) or `pnpm run typecheck` won't see the change.
