# Slideout

The legacy jQuery slide-in panel, `Slideout` (`Craft.Slideout`). Core opens its
screens with the Vue slideout stack (`openSlideout()` / `openSlideoutWith()` in
`@/common/slideouts`); this class remains for two-step verification setup and
for plugins. Craft 5's `CpScreenSlideout` and `ElementEditorSlideout` live in
the yii2-adapter (`yii2-adapter/resources/js/slideout/`).

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

## Extending from legacy code

`Craft.Slideout` is a `compatify()`-wrapped constructor, so the legacy
`Garnish.Base.extend({...instance}, {...statics})` API still works, and
`this.base(...)` inside an overridden method dispatches into the modern
implementation.

## No custom element

Slideouts are always constructed programmatically around dynamic content;
there's no server-rendered markup to boot from, so this module has no
`.ce.ts` / `defineElement()` registration.

## Files

- `slideout.ts` — `Slideout`, `SlideoutSettings`, and the exported
  `uiLayerManager()` lookup (resolves the page's UI-layer manager).
- `support.ts` — the `containerSlideouts` WeakMap.
- `index.ts` — assigns the `Craft.Slideout` global (with the static API
  mirrored onto it). Imported for its side effects from `resources/js/cp.ts`
  and `resources/js/legacy.ts`.
