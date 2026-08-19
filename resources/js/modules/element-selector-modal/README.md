# element-selector-modal

Replaces `Craft.BaseElementSelectorModal` / `Craft.AssetSelectorModal` /
`Craft.VolumeFolderSelectorModal` from the legacy cp bundle, plus the
`createElementSelectorModal` / `registerElementSelectorModalClass` registry that
used to live in `cp/src/js/Craft.js`.

## Shape

This module does **not** follow the `<name>.ce.ts` + `support.ts` shape described
in [the modules README](../README.md). That pattern is for behavior wrapped
around server-rendered markup; a selector modal has none — it is constructed
imperatively and builds its own container. So there is no custom element and no
element-keyed instance registry.

What does carry over is the rest of the pattern: the module owns the behavior,
`window.Craft.*` is a shim, and the legacy source is deleted.

| File | Role |
| --- | --- |
| `base-element-selector-modal.ts` | The base modal, on `@craftcms/garnish` `Modal`. |
| `asset-selector-modal.ts` | Adds the "Select transform" menu. |
| `volume-folder-selector-modal.ts` | Folder picker used by asset moves. |
| `registry.ts` | Element type → modal class map, and the factory. |
| `index.ts` | Registers the built-in asset mapping, assigns the `Craft.*` shims, re-exports. |

## Registry

`registry.ts` holds the map. `createElementSelectorModal(elementType, settings)`
constructs the registered class, or `BaseElementSelectorModal`.
`registerElementSelectorModalClass()` throws on a duplicate, as the legacy
registry did.

In-repo callers import the factory directly rather than going through `Craft`.
The globals remain for plugins and for PHP-emitted boots — `MoveAssets.php` and
the legacy `AssetIndex` still emit `new Craft.VolumeFolderSelectorModal(…)`.

### Load order

The legacy bundle is a plain `<script>` and runs before this module, so a plugin
can register a class before there is anywhere modern to put it.
`adoptLegacyRegistrations()` drains anything sitting on
`Craft._elementSelectorModalClasses` on load, and the built-in asset registration
yields to an entry that is already there.

That covers a plugin assigning into the object. A plugin *calling*
`Craft.registerElementSelectorModalClass()` before this module evaluates will
still fail — the function no longer exists in the legacy bundle. If that turns up
in practice, the fix is a queueing stub in the legacy bundle, not moving the
registry back.

## Still jQuery

`base-element-selector-modal.ts` builds its chrome with jQuery (`$body`,
`$footer`, `Craft.ui.createSubmitButton`) and binds the jQuery-synthetic
`doubletap` event. The element index inside the modal is still the legacy
`Craft.AssetIndex` / `BaseElementIndex` via the `Craft.createElementIndex` seam —
the next registry worth porting the same way.
