# Thumbnail Loader

The wiring around `ThumbnailLoader` — the queued, lazy element-thumbnail
loader behind the legacy `Craft.ElementThumbLoader`. It loads `[data-sizes]`
thumb placeholders through a small worker pool (3 workers, 30s give-up), and
defers offscreen thumbs until they become visible (scroll/resize listeners +
the static `retryAll()` retry hook used by previews).

## Pieces

- **Main class** — `src/utilities/thumbnail-loader.ts` (`ThumbnailLoader` +
  `ThumbnailWorker`), exported from `@craftcms/ui` and importable via
  `@craftcms/ui/utilities/thumbnail-loader`. Also instantiated by this
  package's `<craft-chip>`.
- **Custom element** — `<craft-thumbnail-loader>` (this directory). A
  light-DOM controller element that boots a bare `ThumbnailLoader` over its
  own subtree once `[data-sizes]` markup parses, and destroys it (workers +
  visibility listeners) on disconnect. Server-rendered fragments can emit the
  wrapper instead of needing an imperative
  `Craft.cp.elementThumbLoader.load(...)` boot.
- **Legacy shim** — `packages/craftcms-legacy/cp/src/js/ElementThumbLoader.js`
  assigns `Craft.ElementThumbLoader`, a thin subclass whose `load($elements)`
  accepts the legacy jQuery-collection signature: it scans **every** element
  in the collection for `.thumb[data-sizes]` descendants and fans each out to
  the modern `load(root, selector)`.

## What changed

- **`retryAll()` is now static.** It only walks the static `invisibleThumbs`
  registry, and the legacy callers (`Preview`, `LivePreview`) invoke it
  statically (`Craft.ElementThumbLoader.retryAll()`). The shim inherits it.
- **The shim scans full collections again.** An interim version of the shim
  only scanned `$elements[0]`, dropping the rest of multi-row collections
  (`$rows`, `$newElements`); it also imported a bad
  `@craftcms/ui/utilities/thumbnail-loader.ts.mjs` specifier that failed to
  resolve at runtime. Both are fixed (the specifier is now
  `@craftcms/ui/utilities/thumbnail-loader`, an extensionless subpath the
  package exports map resolves to built output).

## Legacy callers that must keep working

All construct `new Craft.ElementThumbLoader()` and/or use the shared
`Craft.cp.elementThumbLoader` instance with jQuery collections:

- `Craft.CP` (creates the shared instance; loads `$pageContainer`, details)
- `BaseElementIndexView` / `TableElementIndexView` (`$rows`, `$newElements`)
- `BaseElementSelectInput`, `NestedElementManager`, `ElementEditor`
- `CpScreenSlideout`, `CpModal`, `Dashboard`, `ImageUpload`, `UserPhotoInput`
- `Preview` / `LivePreview` (static `Craft.ElementThumbLoader.retryAll()`)

## Deferred

Browser verification of thumb loading on legacy index/slideout surfaces is
left to manual testing.
