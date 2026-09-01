# Slideout

Slide-in panels for the control panel. Three classes:

- **`Slideout`** — the base panel. Builds a container around your content and
  manages the shade, open/close transitions, Escape/shade dismissal, focus
  trapping and restoration, and stacking when multiple panels are open.
- **`CpScreenSlideout`** — a `Slideout` that loads a CP screen from a
  controller action, with header/tab/toolbar chrome, an optional details
  sidebar, a Cancel/Save footer, and delta-aware form submission.
- **`ElementEditorSlideout`** — a `CpScreenSlideout` for the `elements/edit`
  screen, pairing the slideout with a `Craft.ElementEditor` that takes over
  drafts, tabs, and submission once the screen loads. Usually reached via
  `Craft.createElementEditor(elementType, element, settings)`.

All three are module exports and page globals (`Craft.Slideout`,
`Craft.CpScreenSlideout`, `Craft.ElementEditorSlideout`).

## `Slideout`

```ts
import {Slideout} from '@/modules/slideout';

const slideout = new Slideout(contents, {
  containerElement: 'form',
  containerAttributes: {id: 'my-slideout'},
});

slideout.on('close', () => {
  // …
});
```

`contents` is anything jQuery's `.append()` accepts — an element, jQuery
collection, HTML string, or array of those.

### Settings

| Setting | Default | Description |
| --- | --- | --- |
| `containerElement` | `'div'` | Tag name for the generated container. |
| `containerAttributes` | `{}` | Attributes for the container element. |
| `autoOpen` | `true` | Open immediately on construction. |
| `closeOnEsc` | `true` | Escape closes the panel. |
| `closeOnShadeClick` | `true` | Clicking the shade closes the panel. |
| `triggerElement` | `null` | Element to refocus on close. Falls back to `document.activeElement` at open time. |

### Events

`open`, `beforeClose`, and `close`, via garnish pub/sub (`slideout.on(...)`).

### Stacking & statics

Open panels stack, each one pushed further from the viewport edge. The stack
itself lives in `@/common/slideouts/panel-stack` and is **shared with the Vue
slideouts**, so the two kinds interleave — a Vue panel opened over one of these
moves it back, and both answer to the same shade, scroll lock, and Escape
ordering. `open()` registers with it; `close()` unregisters.

- `Slideout.openPanels` — open panels of *this* kind, most recently opened
  first. Vue panels aren't in here; use `stackedPanels()` from the shared
  module for everything that's open.
- `Slideout.instances` — instances keyed by container id (only for slideouts
  whose container has an `id`).
- `Slideout.totalPanels()` / `positionProp()` / `addPanel()` / `removePanel()` /
  `updateStyles()` — kept as public API; the last three now delegate to the
  shared stack.
- `position(index, total)` / `handleShadeClick()` / `suppressShade()` — what
  the shared stack calls back into. `CpScreenSlideout` overrides
  `handleShadeClick()` so a shade click runs its unsaved-changes check.

### Finding an instance

- `containerSlideouts` (module export) — a `WeakMap` from the container
  element to its instance.
- `$container.data('slideout')` — the jQuery back-reference (CP screens also
  set `data('cpScreen')`).

## `CpScreenSlideout`

```ts
import {CpScreenSlideout} from '@/modules/slideout';

const slideout = new CpScreenSlideout('fields/edit-field', {
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

All three globals are `compatify()`-wrapped constructors, so the legacy
`Garnish.Base.extend({...instance}, {...statics})` API still works, and
`this.base(...)` inside an overridden method dispatches into the modern
implementation.

One rule for legacy bundles: **don't call `.extend()` on these globals at
script-eval time.** They're assigned by module scripts, which may execute
after a classic bundle evaluates. Resolve the class lazily on first use
(e.g. behind an `Object.defineProperty` getter).

## No custom element

Slideouts are always constructed programmatically around dynamic content;
there's no server-rendered markup to boot from, so this module has no
`.ce.ts` / `defineElement()` registration.

## Files

- `slideout.ts` — `Slideout`, `SlideoutSettings`, and the exported
  `uiLayerManager()` lookup (resolves the page's UI-layer manager).
- `cp-screen-slideout.ts` — `CpScreenSlideout`, `CpScreenSlideoutSettings`.
- `element-editor-slideout.ts` — `ElementEditorSlideout`,
  `ElementEditorSlideoutSettings`.
- `support.ts` — the `containerSlideouts` WeakMap.
- `index.ts` — assigns the `Craft.*` globals (with the static API mirrored
  onto them) and registers the open/close hook that repositions open HUDs.
  Imported for its side effects from `resources/js/cp.ts` and
  `resources/js/legacy.ts`.
