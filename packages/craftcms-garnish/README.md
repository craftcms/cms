# @craftcms/garnish

Modern, tree-shakeable TypeScript rewrite of Craft CMS's Garnish UI library.

It ships **two surfaces from one package**:

- A **modern**, jQuery-free, ESM-only API of native `class`es, native events, and
  tree-shakeable utilities — the preferred surface for new code.
- An opt-in **compat** layer (`@craftcms/garnish/compat`) that restores the legacy
  `Garnish.Base.extend()` / `this.base()` / jQuery authoring contract on top of the
  same modern core, so existing Craft plugins can adopt it with (near) zero changes
  and migrate incrementally.

## Features

- jQuery-free modern core (the `.` entry never imports or touches jQuery)
- Native ES classes with a real `extends` / `super` model
- Object pub/sub + namespaced DOM listeners that preserve the legacy event grammar
- Accessible, animated `Modal` (focus trapping, ARIA backgrounding, Web Animations
  API fades that respect `prefers-reduced-motion`)
- A broad utility surface (DOM, focus, ARIA, forms, animation, environment helpers)
- Full TypeScript types
- An opt-in compat layer that wraps every class so `.extend()` / `init` /
  `this.base()` / jQuery args keep working, and installs a legacy-shaped
  `window.Garnish`

## Installation

```bash
npm install @craftcms/garnish
```

jQuery is an **optional peer dependency** — required only by the `compat` entry, and
only at runtime. The modern `.` entry never needs it.

## Usage

### Mode A — Modern (recommended for new code)

Import named exports. ES classes, native events, no jQuery. Anything you don't
import is tree-shaken away.

```ts
import {Modal} from '@craftcms/garnish';

const el = document.querySelector('#my-modal')!;
const modal = new Modal(el, {
  closeOtherModals: true,
  hideOnEsc: true,
  onShow: () => console.log('shown'),
});

// Subscribe to events with the native emitter:
modal.on('hide', () => console.log('hidden'));
modal.on('fadeOut.myPlugin', () => cleanup());
modal.off('.myPlugin'); // remove everything in that namespace

// Later:
modal.hide();
modal.destroy();
```

Subclass with plain `class extends` and `super`:

```ts
import {Modal, type ModalSettings} from '@craftcms/garnish';

class ConfirmModal extends Modal {
  constructor(container: Element, settings?: Partial<ModalSettings>) {
    super(container, settings);
    // ...your setup...
  }

  override onShow(): void {
    super.onShow(); // preserves the `show` event + onShow callback
    // ...custom behavior...
  }
}
```

Utilities are individual named exports — import only what you use:

```ts
import {trapFocusWithin, releaseFocusWithin, getPostData} from '@craftcms/garnish';
```

#### Dragging — `BaseDrag` / `DragMove`

`BaseDrag` is the jQuery-free drag foundation (Pointer Events, native auto-scroll).
`DragMove` is a thin subclass that positions the dragged element under the cursor.
Drag handles need `touch-action: none` so the browser doesn't eat the gesture on
touch devices.

```ts
import {DragMove} from '@craftcms/garnish';

const box = document.querySelector('#draggable')!;
box.style.touchAction = 'none'; // required for touch/pen

const dragger = new DragMove(box, {
  // axis: 'x',                 // optionally lock to one axis
  // handle: '.drag-handle',    // drag only by a child selector
  onDragStart: () => console.log('start'),
  onDrag: () => console.log('moving'),
  onDragStop: () => console.log('stop'),
});

dragger.on('drag', () => {/* same events are also emitted */});
// later: dragger.destroy();
```

For full control, use `BaseDrag` directly and position the element yourself in
`onDrag` (read `mouseX/mouseY`/`mouseOffsetX/mouseOffsetY` off the dragger).

#### Helpers &amp; drop targets — `Drag` / `DragDrop`

`Drag` picks up the selected element(s): on drag start it builds floating
*helper* clones that trail the cursor with lag, and on drop you choose what
happens — animate them back to their source (`returnHelpersToDraggees()`, a Web
Animations API tween that respects `prefers-reduced-motion`) or fade them out
(`fadeOutHelpers()`). `DragDrop` adds drop targets on top: while dragging, the
target under the cursor is highlighted (its `activeDropTargetClass` is toggled)
and `onDropTargetChange` fires; there is no separate `drop` event — read
`$activeDropTarget` inside your own `dragStop` handler to perform the drop.

```ts
import {DragDrop} from '@craftcms/garnish';

const dd = new DragDrop({
  dropTargets: '.dropzone', // selector, element(s), or a () => elements fn
  onDropTargetChange: (active) => console.log('over:', active),
  onDragStop() {
    if (dd.$activeDropTarget) {
      // a raw HTMLElement | null — NOT a jQuery object (drop the `[0]`)
      console.log('dropped on', dd.$activeDropTarget);
    }
    dd.returnHelpersToDraggees(); // send the helper clone home
  },
});
dd.addItems(document.querySelectorAll('.chip')); // items added after construction
```

As with `BaseDrag`, draggable items and handles need `touch-action: none`.

#### Sortable lists — `DragSort`

`DragSort` is the sortable-list dragger built on `Drag`: drag an item to reorder it
within its container, with **live insertion feedback** — as the cursor moves, the
dragged item (and an optional `insertion` placeholder) is re-inserted into the DOM at
the spot it will land, so the surrounding items reflow in real time. On drop the new
order is committed and `sortChange` fires if anything moved. `insertionPointChange`
fires whenever the landing spot moves.

```ts
import {DragSort} from '@craftcms/garnish';

const list = document.querySelector('#sortable')!;
const sort = new DragSort(list.querySelectorAll('li'), {
  container: list, // the sort is constrained to this (heighted) container
  axis: 'y', // single-column list
  insertion: () => {
    const ph = document.createElement('li');
    ph.className = 'insertion';
    return ph; // a placeholder shown at the landing spot
  },
  onInsertionPointChange: () => console.log('insertion moved'),
  onSortChange: () => console.log('new order committed'),
  // gate where items may land:
  // canInsertBefore: (item) => !item.classList.contains('locked'),
});
sort.on('sortChange', () => persistOrder());
```

`magnetStrength > 1` rubber-bands the helper toward the dragged item's home;
`moveTargetItemToFront` keeps a multi-select drag's target leading the block. As with
`BaseDrag`, the sortable items / handles need `touch-action: none`.

#### Anchored popovers — `HUD`

`HUD` is the anchored popover/bubble. It attaches to a trigger element, picks one of
four orientations (`bottom` / `top` / `right` / `left`) from the available clearance
around the trigger, draws a tip pointing back at it, follows the trigger on
scroll/resize, traps `Tab` focus between the trigger and the body, and registers a
UI layer + Escape shortcut (the same `UiLayerManager` `Modal` uses). Append your
content into `hud.$main` — a raw `HTMLElement`, not a jQuery object.

```ts
import {HUD} from '@craftcms/garnish';

const addBtn = document.querySelector('#add-btn')!;
const hud = new HUD(addBtn, {
  hudClass: 'hud fld-library-hud',
  orientations: ['right', 'bottom', 'left'],
  showOnInit: false,
});

hud.on('show', () => hud.$main!.append(library)); // $main is an HTMLElement
hud.on('hide', () => addBtn.focus());
addBtn.addEventListener('click', () => hud.show());
// hud.toggle();  hud.updateSizeAndPosition();  hud.destroy();
```

The constructor mirrors the legacy shape — `new HUD(trigger, bodyContents?, settings?)`,
with a `new HUD(trigger, settings)` param shift — so a legacy
`new Garnish.HUD($addBtn, {...})` call ports across unchanged.

#### Dropdown menus — `DisclosureMenu`

`DisclosureMenu` is the disclosure dropdown/menu: a trigger button (carrying
`aria-controls` / `aria-expanded`) paired with a menu panel referenced by that id.
It anchors the panel below the trigger (flipping above when there's no room),
aligns it left/center/right, manages full keyboard navigation + type-ahead search
and focus, registers a UI layer + Escape shortcut, dismisses on an outside click,
and exposes item/group builders (`addItem` / `addGroup` / `addHr`) consumers use to
populate it. An optional `withSearchInput` filters items live.

```ts
import {DisclosureMenu} from '@craftcms/garnish';

// <button aria-controls="actions" aria-expanded="false">Actions ▾</button>
// <div id="actions" class="menu menu--disclosure"><ul>…</ul></div>
const menu = new DisclosureMenu(document.querySelector('button')!);

menu.addItem({label: 'Rename', onActivate: (el) => rename(el)});
const group = menu.addGroup('Danger zone');
menu.addItem({label: 'Delete', destructive: true, onActivate: () => remove()}, group);

menu.on('show', () => console.log('opened'));
// menu.show();  menu.hide();  menu.isExpanded();  menu.destroy();
```

Selection is per-item: each item's `onActivate` / `callback` runs on activation,
then the menu hides. `DisclosureMenu.getInstance(triggerOrContainer)` is the native
replacement for the legacy `$el.data('disclosureMenu')`. A legacy
`new Garnish.DisclosureMenu($trigger, settings)` call ports across unchanged.

**No-jQuery guarantee:** importing from `@craftcms/garnish` (the `.` entry) never
pulls in jQuery, never reads `window.jQuery`/`$`, and never assigns
`window.Garnish`. Those behaviors live exclusively in the `compat` entry.

### Mode B — Compat / upgrade path (for existing plugins)

The compat entry restores the legacy authoring contract: a `window.Garnish` global,
`.extend()`-able classes, `init()` as the constructor, `this.base()` super-dispatch,
and jQuery-collection constructor arguments.

#### How to ADD the compat layer

Add a single side-effecting import at your bundle entry point:

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

That import installs `window.Garnish` (guarded by
`if (typeof window.Garnish === 'undefined')`, so it never clobbers an existing
legacy-bundle global). jQuery must be present on the global scope for the
jQuery-shaped parts (`$container` args, `$win`/`$doc`/`$bod`, `isJquery`,
`$.fn.activate/textchange/resize` sugar); the layer throws a clear, actionable
error if a jQuery-only feature is used while jQuery is absent.

For programmatic (non-auto-install) use, call `installGarnishCompat()` or work with
the named `compatify` / `GarnishCompat` exports directly:

```ts
import {installGarnishCompat, compatify} from '@craftcms/garnish/compat';
import {Modal} from '@craftcms/garnish';

const Garnish = installGarnishCompat();
const LegacyModal = compatify(Modal); // one class, without the global
```

#### How to DROP the compat layer

The compat layer is **opt-in and tree-shakeable** — you remove it by removing its
import. Migrate one class at a time:

1. Convert a `Garnish.X.extend({init(){…}})` subclass to
   `class extends X { constructor(){ super(); … } }`, replacing `this.base(...)`
   with `super.method(...)` and importing the modern named export
   (`import {Modal} from '@craftcms/garnish'`). Modern and compat code can coexist
   in the same bundle during the transition.
2. Once nothing depends on `window.Garnish` or the legacy affordances, **delete the
   `import '@craftcms/garnish/compat'` line.** Tree-shaking then drops all of the
   compat code, the `window.Garnish` global, and the jQuery peer requirement.

## Documentation

- [`docs/06-api-reference.md`](docs/06-api-reference.md) — the public API cheat sheet
  (signatures + one-liners) for `Base`, `Modal`, the drag classes (`BaseDrag`,
  `DragMove`, `Drag`, `DragDrop`), the `Garnish` namespace utilities, and the compat
  exports.
- [`docs/00-migration-plan.md`](docs/00-migration-plan.md) §2 — the compat design and
  upgrade-path rationale.
- [`docs/01-core-design.md`](docs/01-core-design.md) — core architecture and the
  utility-by-utility port plan.
- [`docs/03-modal-slice.md`](docs/03-modal-slice.md) — the Modal PoC contract.

Public API symbols carry TSDoc — your editor's IntelliSense is the fastest reference.

## Development

```bash
npm run dev          # Storybook dev server at http://localhost:6006
npm run storybook    # alias of `npm run dev`
npm run build:storybook  # static Storybook build
npm run build        # production build (dual `.` + `/compat` entries)
npm run build:watch  # tsdown watch build
npm run test         # Vitest suite
npm run check:types  # tsc --noEmit (includes stories)
npm run format       # Prettier (writes ./src ./tests ./stories ./.storybook)
```

Interactive component demos live in **Storybook** (`npm run dev` or
`npm run storybook` → http://localhost:6006), with one story file per component
under `stories/`. Stories import the real source from `../src`, so edits
hot-reload instantly. See [`docs/17-storybook-notes.md`](docs/17-storybook-notes.md)
for how stories are organized, the shared event-log helper, and how to add a story
when porting a new component.

## Status

This is the vertical-slice proof of concept. The modern core, `Base`, `Modal`, the
drag foundation, and the compat layer are complete and tested.

- **`BaseDrag` / `DragMove`** are implemented (Pointer Events, native auto-scroll)
  and available as named exports.
- **`Drag` / `DragDrop` / `DragSort`** are **supported** and available as named
  exports. `Drag` adds helper clones + return-to-source / fade-out (Web Animations
  API, reduced-motion aware); `DragDrop` adds drop targets + hit detection; `DragSort`
  adds sortable lists with live insertion feedback (the `_getClosestItem` spatial
  hit-test + midpoint caching, the `insertion` placeholder, and `sortChange` /
  `insertionPointChange` events).
- **`Modal` `draggable` / `resizable`** are **supported** (still `false` by default).
  A draggable modal uses `DragMove` on its container — or on the element matched by
  `dragHandleSelector` for a header-only handle — and a resizable modal uses
  `BaseDrag` on a generated corner handle. (They previously threw; that limitation
  is gone.)
- **`HUD`** is **supported** (Phase 3): the anchored popover with smart 4-way
  positioning, a tip/arrow, scroll-follow, focus trapping, and `UiLayerManager`
  layer + Escape integration. This was the last `FieldLayoutDesigner` overlay
  blocker.
- **`DisclosureMenu`** is **supported** (Phase 3): the disclosure dropdown/menu
  with above/below + left/center/right positioning, full keyboard navigation +
  type-ahead search, focus management, `UiLayerManager` layer + Escape, an
  optional search input, and the item/group builder surface (~19 CP sites depend
  on it). It was the largest remaining component (~1,008 LOC).

The **drag cluster is COMPLETE** — `BaseDrag`, `DragMove`, `Drag`, `DragDrop`, and
`DragSort` are all ported, with no drag modules pending. The overlay/menu set
(`Modal` + `HUD` + `DisclosureMenu`) is ported too.

## License

MIT © Pixel & Tonic, Inc.
