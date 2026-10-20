# Slideouts (Craft 5 API)

Craft 5's CP screen and element editor slideouts, for plugins:
`Craft.CpScreenSlideout`, `Craft.ElementEditorSlideout`, and
`Craft.createElementEditor()`. Core doesn't use them; it opens screens with
`Craft.openSlideout()` and the declarative `craft:open-slideout` /
`craft:edit-element` actions. Both classes build on core's `Slideout`
(`resources/js/modules/slideout/`).

When the page has the Vue slideout stack, both classes open their screen there
instead of building the legacy panel, and relay its events back to the
instance — see below.

- **`CpScreenSlideout`** — a `Slideout` that loads a CP screen from a
  controller action, with header/tab/toolbar chrome, an optional details
  sidebar, a Cancel/Save footer, and delta-aware form submission.
- **`ElementEditorSlideout`** — a `CpScreenSlideout` for the `elements/edit`
  screen, pairing the slideout with a `Craft.ElementEditor` that takes over
  drafts, tabs, and submission once the screen loads. Usually reached via
  `Craft.createElementEditor(elementType, element, settings)`.

## `CpScreenSlideout`

```js
const slideout = new Craft.CpScreenSlideout('fields/edit-field', {
  params: {fieldId: 1},
});

slideout.on('submit', ({response, data}) => {
  // …
});
```

The screen's content is fetched from the action with a GET (any in-flight
request is cancelled first), and the response drives the UI: `content`,
`tabs`, `sidebar`, `actionMenu`, `editUrl`, `notice`, `submitButtonLabel`,
plus `headHtml`/`bodyHtml` for the screen's assets. Submitting POSTs the form
back with delta serialization; Ctrl+S submits, Escape and the shade go
through the dirty-check (`closeMeMaybe()` — a confirm when there are unsaved
changes). Sidebar visibility persists in the `sidebar-slideout` cookie.

### Settings

On top of the `Slideout` settings (note `closeOnEsc`/`closeOnShadeClick`
default to `false` here — the class provides its own Escape/shade handling
through the dirty-check):

| Setting | Default | Description |
| --- | --- | --- |
| `params` | `{}` | Extra request params for `load()`. |
| `requestOptions` | `{}` | Merged into the `load()` request config. |
| `showHeader` | `null` | Force the header visible regardless of content. |
| `closeOnSubmit` | `true` | Close after a successful submit. |
| `onLoad` / `onSubmit` | — | Callbacks, alongside the equivalent events. |

### Events

`beforeLoad`, `load`, and `submit` (`{response, data}`, where `data` is the
saved model's payload).

### Opening in the Vue slideout stack

When the page has the Vue slideout stack, `new CpScreenSlideout(action)` (or
the `Craft.CpScreenSlideout` global) opens the screen there instead: `action`
is fetched as an action URL, or as is when it's a URL, with `params` appended.
The `load`, `submit` and `close` events and the `onLoad`/`onSubmit` callbacks
still fire, `close()` closes the panel, and `closeMeMaybe()` goes through the
panel's unsaved-changes check. The panel always closes after a save, so
`closeOnSubmit: false` has no effect, and the legacy DOM members (`$container`,
`$footer`, …) and methods such as `reload()` aren't available.

`ElementEditorSlideout` (and so `Craft.createElementEditor()`) does the same
with the `elements/edit` action, also calling `onSaveElement`. It keeps the
legacy panel when given `onBeforeSubmit` or `saveParams`, which drive the
legacy `Craft.ElementEditor`; `elementEditor` and `$element` aren't available
otherwise.

Other subclasses, including legacy `.extend()` subclasses, keep the legacy
panel, since they may build on its markup.

### Useful methods

`load()` / `reload()`, `isDirty()`, `closeMeMaybe()`, `showErrors(errors)` /
`clearErrors()` (field + tab error indicators), `showSidebar()` /
`hideSidebar()`, `showSubmitSpinner()` / `hideSubmitSpinner()`.

## The jQuery API surface

The `$`-prefixed instance members are jQuery collections and are public API —
external code reads them directly: `$container`, `$outerContainer`, `$shade`
(the shade shared with every other open panel — don't remove it),
and on CP screens `$header`, `$toolbar`, `$content`, `$sidebar`, `$footer`,
`$saveBtn`, etc. The same goes for the jQuery data entries the submit flow
maintains: `data('initialSerializedValue')`, `data('delta-names')`,
`data('initial-delta-values')`, and the optional `data('serializer')`
override used by `isDirty()`.

## Extending from legacy code

Both globals are `compatify()`-wrapped constructors, so the legacy
`Garnish.Base.extend({...instance}, {...statics})` API still works, and
`this.base(...)` inside an overridden method dispatches into the modern
implementation.

One rule for legacy bundles: **don't call `.extend()` on these globals at
script-eval time.** They're assigned by module scripts, which may execute
after a classic bundle evaluates. Resolve the class lazily on first use
(e.g. behind an `Object.defineProperty` getter).

## Files

- `cp-screen-slideout.ts` — `CpScreenSlideout`, `CpScreenSlideoutSettings`.
- `element-editor-slideout.ts` — `ElementEditorSlideout`,
  `ElementEditorSlideoutSettings`.
- `index.ts` — assigns the `Craft.CpScreenSlideout`,
  `Craft.ElementEditorSlideout`, and `Craft.createElementEditor` globals.
  Imported from `cp-compat.ts`.
