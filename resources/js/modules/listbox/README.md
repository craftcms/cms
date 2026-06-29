# Listbox

A TypeScript port of Craft's `Craft.Listbox` — a single-select toggle group
that turns a container of `button` / `[type=button]` / `craft-button` options
into a set where exactly one option is "pressed" at a time (`aria-pressed="true"`
+ the `selectedClass`). It powers `_includes/forms/buttonGroup.twig` and is
constructed programmatically by several legacy CP screens.

## What changed

- **Moved out of `@craftcms/garnish`.** The class previously lived at
  `packages/craftcms-garnish/src/listbox.ts`. It now lives here in the app,
  following the controller-class + self-booting custom element pattern (matching
  `sortable-checkbox-select` / `field-layout-designer`). It still extends the
  `Base` primitive imported from `@craftcms/garnish`.
- **Constructor unwraps jQuery containers.** Legacy callers pass a jQuery
  collection (`new Craft.Listbox($container, {…})`). When the resolved container
  has a truthy `.jquery` property it is unwrapped to its first native element; a
  raw `Element` is accepted as-is. (A jQuery object is not a plain object, so the
  `new Listbox(settings)` param-shift guard correctly leaves it as the
  container.)
- **Global subclass jQuery-wraps `onChange`.** `window.Craft.Listbox` is a thin
  `CraftListboxGlobal` subclass (in `index.ts`) that overrides `setSettings` to
  re-wrap the `onChange` option with `$()` — the modern class calls `onChange`
  with a RAW element, but the legacy callers expect a jQuery collection. This
  replaces the old `wrapListboxOnChange` shim that lived in the garnish
  `compat.ts`.
- **`<craft-listbox>` self-boots.** A new custom element boots the `Listbox`
  around the `.btngroup` it wraps and syncs the optional hidden input on change,
  so `buttonGroup.twig` no longer needs a `{% js %}` block. The WC uses the bare
  `Listbox` (raw elements), not the jQuery-wrapping global.
- **Removed the stray `console.log('garnish listbox')`.**
- **`.data()` → WeakMap.** The container→instance back-reference is kept in a
  module-level `WeakMap` in `support.ts` (`containerListboxes`), used as the
  double-instantiation guard.

## Legacy callers that must keep working

These construct `new Craft.Listbox(...)` and rely on the jQuery-wrapping global:

- `CustomizeSourcesModal` (`$selectedOption.data(...)`)
- `BaseElementIndex`
- `Preview` (`.switchDeviceType($opt)`)
- `AssetImageEditor`

They are unchanged; they keep working via the `CraftListboxGlobal` global.

## Files

- `Listbox.ts` — the `Listbox` class (+ `ListboxSettings`).
- `support.ts` — the `containerListboxes` WeakMap (back-ref + double-init guard).
- `Listbox.wc.ts` — the `<craft-listbox>` custom element (uses the bare class,
  syncs the hidden input).
- `index.ts` — assigns `window.Craft.Listbox` (the jQuery-wrapping subclass) and
  `defineElement('craft-listbox', …)`. Imported for side effects from
  `resources/js/cp.ts`.

## Deferred

Behavioral/browser verification (clicking options, the legacy programmatic
callers, and the hidden-input sync) is left to manual testing.
