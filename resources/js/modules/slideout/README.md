# Slideout

A TypeScript port of the legacy jQuery `Craft.Slideout`
(`packages/craftcms-legacy/cp/src/js/Slideout.js`) onto the modern
**`@craftcms/garnish`** `Base`. A slideout is the panel that slides in from the
edge of the viewport for things like element edit screens, CP screens, and
auth-method setup flows.

## No custom element

Unlike the other ported modules, there's no `slideout.ce.ts` and `index.ts`
registers no `defineElement(...)`. Slideouts are always constructed
programmatically around dynamic content (`new Craft.Slideout(contents,
settings)`, `new Craft.CpScreenSlideout(action, settings)`) — there's no
server-rendered markup for a custom element to boot around. That absence is
this module's version of the "shim," the same way `component-select`
deliberately has no `window.Craft.*` assignment for its own reasons.

## The subclass problem: `compatify`, not a plain class

Every other port assigns the plain modern class to `window.Craft.*`, because
nothing subclasses it via the legacy `.extend()` API. **This one is different.**
`Craft.CpScreenSlideout` (`packages/craftcms-legacy/cp/src/js/CpScreenSlideout.js`)
still subclasses `Craft.Slideout` with the legacy Dean-Edwards
`Garnish.Base.extend({...instance}, {...statics})` call, and its `init` invokes
`this.base(contents, settings)` expecting that to reach the ancestor's `init`.
`Craft.ElementEditorSlideout` subclasses `CpScreenSlideout` the same way.

So `index.ts` assigns:

```ts
window.Craft.Slideout = compatify(Slideout);
```

`compatify()` (`packages/craftcms-garnish/src/compat.ts`) turns the modern
class into a legacy-shaped constructor with a real `.extend()`: each `.extend()`
call produces an actual subclass (native prototype chain), and any instance
method whose source references `base` gets wrapped so `this.base` resolves,
per-call, to the ancestor implementation found by walking that prototype
chain. Concretely: `CpScreenSlideout.init`'s `this.base($contents, settings)`
resolves to `Slideout.prototype.init` — this module's {@link init} — because
`compatify(Slideout)` is the root of the chain `CpScreenSlideout.extend(...)`
built on top of.

To make that possible, {@link Slideout.init} follows the same construction
contract as every other port: it's a real public method, invoked from the
constructor only for the leaf class via a `new.target === Slideout` guard, so
a `compatify()`-built subclass's `init` can reach it through `this.base()`.

## What stayed jQuery, and why

`$outerContainer`, `$container`, `$shade`, `$triggerElement`, and `$liveRegion`
are still jQuery collections — not native elements — because the subclass and
external code depend on that shape:

- `ElementEditor.js`: `this.$container.data('slideout')`
- `CP.js`: `$modal.find('.slideout').data('slideout')`
- `CpScreenSlideout.js`/`ElementEditorSlideout.js`: jQuery-heavy internals
  throughout (`this.$container.serialize()`, `.find()`, `.css()`, etc.)

`declare const $: any` / `declare const Craft: any` are page globals, same as
every other port. `this.$container.data('slideout', this)` still runs in
{@link init} — nothing about the public jQuery surface changed — and the
container is additionally registered in the new `support.ts` `containerSlideouts`
WeakMap.

`Craft.trapFocusWithin`, `Craft.setFocusWithin`, `Craft.useMobileStyles`, and
`Craft.slideoutPosition` stay `Craft` seams (unchanged call sites), even though
`@craftcms/garnish` has its own modern focus-trap utilities now — those are
Craft-specific wrappers other code also depends on. `Craft.Preview.getActive()` /
`Craft.LivePreview.getActive()` stay Craft seams too.

### The shared `$liveRegion` quirk

The legacy prototype default was a **single jQuery element literal**
(`$liveRegion: $('<span .../>')`), evaluated once when `Garnish.Base.extend()`
ran — every `Craft.Slideout` instance shared the exact same `<span>` node,
moved (via `.appendTo()`, not cloned) into whichever container most recently
called `init`. This port keeps that faithfully: `$sharedLiveRegion` is a
module-scoped singleton, not a fresh per-instance element. It's very possibly a
latent bug in the original (stacked slideouts fight over one live region), but
"fix it" wasn't in scope for this port — changing it would be a behavior
change, not a port.

## Statics: mutable shared state, mirrored onto the compat global

Legacy code reaches `Craft.Slideout.defaults`, `.instances` (an object keyed by
container id), `.openPanels` (an array), `.positionProp()`, `.totalPanels()`,
`.addPanel()`, `.removePanel()`, and `.updateStyles()`. These are implemented
as real `static` members on the modern `Slideout` class in `slideout.ts`, and
`index.ts` copies references to all of them onto the `compatify()`-built
constructor (`Object.assign(CompatSlideout, {...})`).

For the mirrored `instances`/`openPanels` references to stay live — rather
than going stale the first time the modern code "updates" one of them — two
legacy *reassignments* became in-place mutations:

- `removePanel` used to do `Craft.Slideout.openPanels = Craft.Slideout.openPanels.filter(...)`.
  The port does `Slideout.openPanels.splice(index, 1)` instead.
- `destroy` used to do `Craft.Slideout.instances = Craft.filterObject(Craft.Slideout.instances, ...)`.
  The port does an in-place `delete Slideout.instances[key]` for this
  instance's key(s) instead.

Both produce the same observable result (the entry disappears), but only the
in-place version keeps `window.Craft.Slideout.instances`/`.openPanels` pointing
at the same array/object the modern statics operate on.

## Class-level `open`/`close` hook (HUD repositioning)

The legacy file ended with `Garnish.on(Craft.Slideout, ['open', 'close'], () =>
{...})`, repositioning any open `Garnish.HUD` instances whenever a slideout
opens or closes. `index.ts` reproduces this as `Garnish.on(Slideout, 'open
close', ...)`, registered against the **modern** `Slideout` class rather than
the compatified constructor. `Base.trigger`'s class-event dispatch
(`ClassEventBus.dispatch` in `packages/craftcms-garnish/src/events.ts`) does an
`instanceof` check against whatever was registered — and since `compatify()`
builds `Craft.CpScreenSlideout` (and `Craft.ElementEditorSlideout`) as real
subclasses via the native prototype chain, their instances still satisfy
`instanceof Slideout`. Registering against the modern class therefore still
catches `open`/`close` events triggered by legacy-extended instances.

## The eval-order hazard: lazy `CpScreenSlideout`/`ElementEditorSlideout`

`Craft.Slideout` is now assigned by this Vite-bundled module, while
`CpScreenSlideout.js`/`ElementEditorSlideout.js` still ship in the separately-built
legacy webpack `cp` bundle and used to call `.extend()` **at module-eval
time**. Those two bundles load independently — nothing guarantees the Vite
bundle's `window.Craft.Slideout = compatify(Slideout)` assignment runs before
the legacy `cp` bundle evaluates `CpScreenSlideout.js`.

Both files were changed to define their `Craft.*` export **lazily**, via
`Object.defineProperty(Craft, 'Name', {get() {...}})`: the getter builds the
`.extend(...)` result (byte-identical body, just wrapped) on first *access*,
caches it by replacing itself with a plain value, and returns it. By the time
anything actually reads `Craft.CpScreenSlideout` or `Craft.ElementEditorSlideout`
— constructing one in response to a user action — every entrypoint's modules
have finished loading, so `Craft.Slideout` is guaranteed to exist.

`ElementEditorSlideout.js` needed the same treatment even though it never
references `Craft.Slideout` directly: it reads `Craft.CpScreenSlideout.extend`
at module-eval time, which would otherwise eagerly trigger *that* getter (and
therefore `Craft.Slideout.extend`) at the same unsafe moment, defeating the
fix one file downstream.

`packages/craftcms-legacy/authmethodsetup/src/AuthMethodSetup.js` has the
identical hazard (`Craft.AuthMethodSetup.Slideout = Craft.Slideout.extend({...})`
at module-eval time), but it's a separate webpack entry point
(`authmethodsetup`), out of scope for this port — left as a known follow-up.

## Files

- `slideout.ts` — the `Slideout` class, `SlideoutSettings`, and the module-scoped
  `$sharedLiveRegion`.
- `support.ts` — the `containerSlideouts` WeakMap (container → instance).
- `index.ts` — `compatify(Slideout)` assigned to `window.Craft.Slideout`, the
  static-API mirror, and the `Garnish.on(Slideout, 'open close', ...)` HUD hook.
  Imported for its side effects from both `resources/js/cp.ts` and
  `resources/js/legacy.ts`.

## Deferred

- Porting `Craft.CpScreenSlideout` and `Craft.ElementEditorSlideout` themselves
  out of the legacy `.extend()` API and onto `compatify`-free modern subclasses
  is out of scope for this port.
- Behavioral/browser verification (open/close transitions, shade, mobile vs.
  desktop styles, Live Preview width tracking, panel stacking with multiple
  open slideouts, and the `CpScreenSlideout`/`ElementEditorSlideout` subclass
  chain) is left to manual testing.
