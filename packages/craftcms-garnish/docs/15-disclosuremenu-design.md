# 15 — `DisclosureMenu` Design

> Phase 3 of the migration plan (doc 00): the disclosure dropdown/menu. **HIGH**
> difficulty — the largest remaining UI component (~1,008 LOC). The risk is the
> combination of anchored positioning, full keyboard nav + type-ahead search,
> focus management, and the item/group builder surface that ~19 CP sites depend
> on.
>
> `DisclosureMenu` extends `Base` and reuses the overlay infrastructure already
> shipped: the `UiLayerManager` (layer stacking + the Escape shortcut), the
> focus/focusable utilities, the native DOM/scroll utils that replace jQuery, and
> the WAAPI fade approach `Modal` uses. It is **not** a `HUD` subclass — the menu
> panel is **pre-existing markup** referenced by the trigger's `aria-controls`,
> not a tree the component builds.

## 1. What a DisclosureMenu is

A disclosure menu attaches a **menu panel** to a **trigger** button. It:

1. Resolves the panel from the trigger's `aria-controls` id (or the trigger's
   next sibling), moves it to the end of `<body>`, and toggles it on click.
2. Anchors the panel **below** the trigger when there's room, flipping **above**
   when there isn't, and aligns it **left / center / right** (from
   `data-align`) within the viewport — repositioning on scroll/resize.
3. Manages **keyboard navigation** (Up/Down/Left/Right move between items; Tab
   cycles out to the trigger / the next focusable element) and **type-ahead
   search** (printable keys focus the first item whose text matches the buffer).
4. Manages **focus**: focuses the first item on show and restores focus to the
   trigger on hide.
5. Registers a **UI layer** + an **Escape** shortcut via the shared
   `UiLayerManager`, like `Modal`/`HUD`, and dismisses on an **outside
   mousedown**.
6. Exposes **item/group builders** (`addItem` / `addItems` / `addGroup` /
   `addHr` / `removeItem` / …) consumers use to populate the panel
   programmatically.

Unlike `HUD`, show is **instant** (legacy only animates the *hide*): show adds
the `visible` class + `opacity: 1`; hide fades the panel out via the Web
Animations API (`.velocity('fadeOut')` → WAAPI), gated on `prefersReducedMotion`.

## 2. Public contract

### Constructor

```ts
new DisclosureMenu(trigger: ElementInput, settings?: Partial<DisclosureMenuSettings>)
```

- `trigger` is coerced to a single `HTMLElement` via `getElement` (the native
  replacement for `$(trigger)`). It must carry `aria-controls` pointing at the
  panel, or have the panel as its next sibling.
- **Param shift: N/A.** Unlike `Modal`/`HUD`, the legacy `init(trigger, settings)`
  has no body-contents argument, so there is no plain-object first-arg shift. The
  only constructor flexibility is `ElementInput` coercion of the trigger (a
  selector string / element / element list).
- On construction the menu resolves + relocates the panel, sets
  `data-disclosure-trigger` on the trigger, defaults `aria-expanded="false"`,
  captures the alignment element, wires its listeners, optionally adds a search
  input, and registers itself in `DisclosureMenu.instances`. A second
  construction on the same trigger **warns and bails** (legacy
  double-instantiation guard).

### Settings (`DisclosureMenuSettings`) + defaults

| Key | Default | Description |
| --- | --- | --- |
| `position` | `null` | `'below'` pins the panel below regardless of clearance; any other value lets the clearance logic pick above/below. |
| `windowSpacing` | `5` | Min gap (px) between the panel and the viewport edge. |
| `withSearchInput` | `false` | Render a search input that filters items live. Also enabled when the panel carries `data-with-search-input`. |

### Public properties

`$trigger`, `$container`, `$alignmentElement`, `$nextFocusableElement`,
`$searchInput` — all `HTMLElement | null`, keeping the legacy `$`-prefixed names
for consumer parity. `searchStr: string`, `clearSearchStrTimeout`,
`infoIconActivated: boolean`.

### Methods + overridable hooks

| Member | Description |
| --- | --- |
| `show()` / `hide()` | Show / hide; no-op if already in that state (or, for show, if the trigger is `.disabled`). |
| `handleTriggerClick()` | Toggle (re-captures the alignment element first). |
| `isExpanded()` | Whether `aria-expanded === 'true'`. |
| `setContainerPosition()` | The above/below + left/center/right positioning pass (also a scroll/resize handler). |
| `captureAlignmentElement()` | (Re)resolve the `data-align-to` descendant (or the trigger). |
| `focusElement(el \| 'prev' \| 'next')` | Focus a specific element or move within the menu's focusables. |
| `handleKeypress(ev)` | Arrows / Tab cycle / type-ahead (bound to the container). |
| `handleMousedown(ev)` | Outside-click dismissal. |
| `addItem` / `addItems` / `createItem` / `addList` / `addGroup` / `addHr` | Item/group builders. |
| `toggleItem` / `showItem` / `hideItem` / `toggleGroup` / `removeItem` | Item visibility. |
| `updateVisibility` / `hasVisibleItems` / `getFirstDestructiveGroup` / `isPadded` | Visibility/query helpers. |
| `clearSearchStr()` | Reset the type-ahead buffer + timeout. |
| `destroy()` | Drop registrations, remove from `instances`, base teardown. |

### Statics

`DisclosureMenu.instances: DisclosureMenu[]`, `DisclosureMenu.defaults`,
`DisclosureMenu.getInstance(el)` (reads the trigger/container → instance
`WeakMap`, the native replacement for `$el.data('disclosureMenu')`).

### Events

`beforeShow`, `show`, `hide`, and the base `destroy`. **Item selection is
per-item:** each item's `onActivate` / `callback` runs on the item's `activate`
event, after which the menu hides. There is no menu-level `optionselect` event
(that belongs to the legacy `MenuBtn`/`Menu`, not `DisclosureMenu`).

## 3. The positioning algorithm

Run in `setContainerPosition()` (also wired as the scroll/resize handler). A
faithful port of the legacy clearance math:

1. **Measure.** Viewport (`window.innerWidth/innerHeight`, `window.scrollX/Y`);
   the alignment element's document-relative offset (rect + scroll, jQuery
   `.offset()` parity) and border-box size (`getOuterWidth/Height`).
2. **Min width.** Set the panel's `minWidth` to the alignment width minus the
   panel's own horizontal padding+border (so the panel is at least as wide as the
   trigger). Clamp `maxWidth` to the viewport when the panel overflows it.
3. **Above vs below.** Compute `topClearance` / `bottomClearance`. Place **below**
   (top = alignment bottom) when `position === 'below'`, or there's room below, or
   above is even tighter; otherwise place **above** (top = alignment top −
   min(menuHeight, topClearance − windowSpacing)). Either way set `maxHeight` to
   the chosen side's clearance − `windowSpacing`.
4. **Horizontal align.** Read `data-align` (`left` default / `center` / `right`).
   Center when the panel fills the viewport or `align === 'center'`; otherwise
   compute left/right clearance and pick `_alignLeft` / `_alignRight` /
   `_alignCenter`, each clamped to `>= 0`.

## 4. Keyboard-nav + type-ahead model

- **Arrows.** Right/Down → `focusElement('next')`; Left/Up → `focusElement('prev')`
  — move within `getFocusableElements(container)` from the currently-focused item.
- **Tab cycle.** On the first focusable, Shift-Tab returns to the trigger; on the
  last, Tab moves to `$nextFocusableElement` (the focusable after the trigger in
  the document, wired on show — Shift-Tab on *it* returns to the panel's last
  item). Tabbing on the **trigger** while expanded moves focus into the panel.
- **Type-ahead.** A printable key (regex `/^[^ ]$/`, plus space once the buffer is
  non-empty) appends to `searchStr`. The first `<li>` whose cached **searchText**
  `startsWith(searchStr)` is focused. The searchText is the `<li>`'s text with
  nested `<svg>`s stripped, lowercased and `trimStart()`ed, cached per `<li>` in a
  `WeakMap` (legacy `$o.data('searchText')`). A 1s timeout clears the buffer.
- **Search input** (`withSearchInput`): typing filters `<li>`s by substring
  (toggling the `filtered` class), un-filters items under a matching `<h3>`, then
  re-runs `updateVisibility()` + `setContainerPosition()`. Esc clears it; Return
  is swallowed so it doesn't submit a form.

## 5. Focus management

- **On show:** focus the first `getFocusableElements(container)` item, or — when
  the panel has none — make the container itself focusable (`tabindex="-1"`) and
  focus it.
- **On hide:** if focus is inside the panel (`focusIsInside`), restore it to the
  trigger; drop the `$nextFocusableElement` Shift-Tab listener.

## 6. jQuery removals → modern utils

| Legacy (`DisclosureMenu.js`) | Modern replacement |
| --- | --- |
| `$(trigger)` | `getElement(trigger)` |
| `$('#' + id)` / `.next()` | `document.getElementById` / `nextElementSibling` |
| `.attr()` / `.data()` (DOM data) | `getAttribute` / `setAttribute` |
| `.data('disclosureMenu', this)` | module-level `WeakMap` + `DisclosureMenu.getInstance` |
| `$o.data('searchText')` | module-level `WeakMap<HTMLElement, string>` |
| `.find(':focusable')` / `:focusable:first/last` | `getFocusableElements` (`utils/focusable`) |
| `$component.is(':focusable')` | `isFocusable` (`utils/focusable`) |
| `Garnish.getFocusedElement()` + `$.contains` | `focusIsInside` (`utils/focus`) |
| `.scrollParent()` | `getScrollParent` (`utils/scroll`) |
| `$alignmentElement.offset()` | inline `rect.top + scrollY` (jQuery `.offset()` parity) |
| `.outerWidth()` / `.outerHeight()` | `getOuterWidth` / `getOuterHeight` (`utils/dom`) |
| `$container.width()` (content box) | rect − padding − border (`getContentWidth`) |
| `.velocity('fadeOut', …)` | the Web Animations API (gated on `prefersReducedMotion`) |
| `.velocity('stop')` | the tracked animation's `cancel()` |
| `.find/closest/filter/parent/children/eq` | native `querySelector(All)` / `closest` / `children` |
| `.prevUntil/.nextUntil` | a native `siblingsUntil(el, stop, dir)` walk |
| `Garnish.$win/$doc/$bod/$scrollContainer` | `win` / `doc` / `bod` / `globals.scrollContainer` |
| `Garnish.uiLayerManager` | `getUiLayerManager()` (`managers/registry`) |
| `Garnish.isCtrlKeyPressed` | `isCtrlKeyPressed` (`utils/env`) |
| `Craft.getUrl` / `Craft.t` / `Craft.initUiElements` | optional-global accessors (`getCraft()`), graceful fallbacks |
| `$(el).formsubmit()` | **omitted** — the `formsubmit` class + `data-action` are still set (doc 16) |

## 7. Testing strategy

happy-dom has no layout (`getBoundingClientRect`/`offset*`/`getClientRects` are
0/empty) and no `element.animate`. The fade therefore finalizes synchronously
(hide is observable immediately — no RAF/animation mock needed). Focusable
elements get `getClientRects` stubbed to look visible (`makeVisible`, as in the
Modal/HUD/focusable tests).

**Unit-testable (happy-dom, mocked rects):**

- Settings/defaults merge; container resolution (`aria-controls` + next-sibling),
  the no-container throw, `data-disclosure-trigger`, `aria-expanded` defaulting,
  `instances`/`getInstance` registration, the double-instantiation warn+bail,
  selector-string trigger coercion, panel relocation to `<body>`.
- `show`/`hide`/`handleTriggerClick` toggling, `beforeShow`/`show`/`hide` events,
  `aria-expanded` flips, the `.disabled` trigger guard.
- Layer add/remove via a fresh `UiLayerManager`; the Escape shortcut.
- Focus: first-item focus on show, container fallback, trigger restore on hide.
- Keyboard nav (Arrow Up/Down between items, Shift-Tab → trigger, Tab on the
  trigger → into the panel) and type-ahead (single + accumulated buffer).
- Outside-mousedown dismissal vs. inside-mousedown no-op.
- Item building: `addItem` (button/link inference), selected/destructive/disabled
  flags, `formsubmit` + `data-action`, the unsupported-config throw, `addGroup`
  heading + last-group fill, `getFirstDestructiveGroup`, `removeItem` empties the
  group, `addHr`, `hasVisibleItems`.
- Item selection: `onActivate` / `callback` runs on `activate`, then the menu
  hides (a tick later — fake timers).
- Search input: build-on-setting + `data-with-search-input`, live filtering +
  clearing, Return-swallow.
- Positioning math with `getBoundingClientRect`/`offset*` mocked: below when
  there's room, above when there isn't, `position: 'below'` override; `top`/`left`
  get set.
- `destroy` cleanup.

**Playground-only (needs real layout/pointer/CSS):** the panel actually flipping
above as the trigger nears the viewport bottom; left/center/right alignment near
the edges; the scroll-follow reposition on real scroll/resize; the search-input
filter visually collapsing empty groups/separators; the fade-out timing. See
playground **section 12** ("DisclosureMenu — dropdown menu").
