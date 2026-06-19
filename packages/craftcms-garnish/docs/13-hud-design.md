# 13 — `HUD` Design

> Phase 3 of the migration plan (doc 00): the anchored popover/bubble. **MED-HIGH**
> difficulty — the risk is the smart 4-way positioning + the scroll-follow loop.
> HUD is the last blocker for porting `FieldLayoutDesigner`, which does
> `new Garnish.HUD(this.$addBtn, {...})`.
>
> HUD extends `Base` and reuses the overlay infrastructure `Modal` already ships:
> the `UiLayerManager` (layer stacking + the Escape shortcut), the focus utilities,
> and the native DOM/scroll utils that replace jQuery. It is **not** a `Modal`
> subclass — it is anchored to a trigger, not centered in the viewport.

## 1. What a HUD is

A HUD ("heads-up display") attaches a floating panel to a **trigger** element. It:

1. Picks one of four **orientations** (`bottom` / `top` / `right` / `left`) from the
   available clearance around the trigger, in a configurable order of preference.
2. Draws a **tip** (arrow) that points back at the trigger from the chosen side.
3. **Follows** the trigger when the window or the trigger's scroll container
   scrolls or resizes, and when its own main content resizes.
4. Traps `Tab` focus so it cycles between the trigger and the HUD body.
5. Registers a **UI layer** + an **Escape** shortcut via the shared
   `UiLayerManager`, exactly like `Modal`.

Unlike `Modal`, the legacy HUD does **not** animate: `showContainer`/`hideContainer`
are plain `.show()`/`.hide()` display toggles (there is no Velocity call in
`HUD.js`), so there is no Velocity→WAAPI conversion in this port — see doc 14.

## 2. Public contract

### Constructor

```ts
new HUD(
  trigger: ElementInput,
  bodyContents?: HUDBodyContents | Partial<HUDSettings>,
  settings?: Partial<HUDSettings>,
)
```

- **Param shift** (legacy parity): `new HUD(trigger, settings)` is treated as
  `new HUD(trigger, '', settings)` when the second arg is a plain object. This is
  exactly how `FieldLayoutDesigner` calls it: `new Garnish.HUD(this.$addBtn, {…})`.
- `trigger` is coerced to a single `HTMLElement` via `getElement` (the native
  replacement for `$(trigger)`).
- `bodyContents` accepts `string` HTML, a `Node`, or a list of nodes
  (`HUDBodyContents`).
- On construction the HUD builds its DOM tree, detects a `position: fixed`
  ancestor (→ fixed positioning), wires its listeners, and — when `showOnInit`
  (default) — shows immediately (hidden at opacity 0 until the first positioning
  pass, to avoid a flash at the origin).

### Settings (`HUDSettings`) + defaults

| Key | Default | Description |
| --- | --- | --- |
| `shadeClass` | `'hud-shade'` | Class for the shade/backdrop element. |
| `hudClass` | `'hud'` | Class(es) for the HUD container (multi-class string OK). |
| `tipClass` | `'tip'` | Base class for the tip; the orientation suffix is appended. |
| `bodyClass` | `'body'` | Class for the `<form>` body. |
| `headerClass` | `'hud-header'` | Identifies a header within the body content. |
| `footerClass` | `'hud-footer'` | Identifies a footer within the body content. |
| `mainContainerClass` | `'main-container'` | Class for the scrollable wrapper. |
| `mainClass` | `'main'` | Class for the main content element. |
| `orientations` | `['bottom','top','right','left']` | Sides to try, in order. |
| `triggerSpacing` | `10` | Gap (px) between the HUD and its trigger. |
| `windowSpacing` | `10` | Min gap (px) between the HUD and the viewport edge. |
| `tipWidth` | `30` | Tip/arrow width (px). |
| `minBodyWidth` | `200` | Minimum HUD body width (px). |
| `minBodyHeight` | `0` | Minimum HUD body height (px). |
| `withShade` | `true` | Render a shade/backdrop behind the HUD. |
| `onShow` / `onHide` / `onSubmit` | no-op | Registered as `show`/`hide`/`submit` handlers. |
| `closeBtn` | `null` | Element whose `activate` hides the HUD. |
| `listenToMainResize` | `true` | Reposition on the main element's `resize` event. |
| `showOnInit` | `true` | Show immediately on construction. |
| `closeOtherHUDs` | `true` | Hide every other open HUD on show. |
| `hideOnEsc` | `true` | Hide on Escape. |
| `hideOnShadeClick` | `true` | Hide when the shade is clicked. |

### Public properties

`$trigger`, `$fixedTriggerParent`, `$hud`, `$tip`, `$body` (a `<form>`), `$header`,
`$footer`, `$mainContainer`, `$main`, `$shade`, `$nextFocusableElement` — all
`HTMLElement | null`, keeping the legacy `$`-prefixed names so consumers
(`FieldLayoutDesigner` appends its library into `hud.$main`) read unchanged.
`showing: boolean`, `orientation: HUDOrientation | null`, `tipClass: string | null`,
plus the reposition bookkeeping records (`windowWidth/Height`, `scrollTop/Left`,
`mainWidth/Height`, `sp*`).

### Methods + overridable hooks

| Member | Description |
| --- | --- |
| `show(ev?)` / `hide()` | Show / hide (display toggle); no-op if already in that state. |
| `toggle()` | Flip visibility. |
| `showContainer()` / `hideContainer()` | The raw display toggles (overridable). |
| `updateBody(contents)` | Replace body content; re-extract header/footer. |
| `updateRecords()` | Re-measure; returns whether anything changed (drives scroll-follow). |
| `updateSizeAndPosition(force?)` | Schedule a RAF reposition (forced, or when records changed). |
| `updateSizeAndPositionInternal()` | The 4-way positioning pass (runs in the RAF). |
| `submit()` | Fire the `submit` flow. |
| `onShow()` / `onHide()` / `onSubmit()` | Hooks; emit the event. Override + `super.*()`. |
| `destroy()` | Remove DOM, drop from `instances`/`activeHUDs`, base teardown. |

### Statics

`HUD.instances: HUD[]`, `HUD.activeHUDs: Record<string, HUD>` (keyed by instance
namespace, used by `closeOtherHUDs`), `HUD.tipClasses` (orientation → tip suffix),
`HUD.defaults`.

### Events

`show`, `hide`, `submit`, `updateSizeAndPosition`, `destroy`. The `onShow` /
`onHide` / `onSubmit` settings callbacks are registered as handlers for the
matching events at construction (legacy behavior — they fire through the event
system, not by a direct call inside the hook).

## 3. The 4-way positioning algorithm

Run in `updateSizeAndPositionInternal()` (on a RAF). All math is a faithful port
of `HUD.js`'s `updateSizeAndPositionInternal`.

1. **Measure.** Window scroll (`window.scrollX/Y`); trigger border-box size
   (`getOuterWidth/Height`); the trigger's document-relative offset (jQuery
   `.offset()` → `rect.top + scrollY`) **and** its scroll-container-relative offset
   (`getOffset`, which adds the scroll container's scroll when it isn't the
   window). For a fixed trigger, offsets are de-scrolled and the scroll terms
   zeroed. The HUD body's content box is measured with `getContentWidth/Height`
   (jQuery `.width()/.height()` parity = border-box rect minus padding+border).

2. **Clearances.** Compute the available space on each side of the trigger:

   ```
   bottom = windowHeight + scScrollTop  - triggerBottom
   top    = triggerTop   - scScrollTop
   right  = windowWidth  + scScrollLeft - triggerRight
   left   = triggerLeft  - scScrollLeft
   ```

3. **Pick the side.** Walk `settings.orientations` in order. The "relevant size"
   is the body **height** for `top`/`bottom` and the body **width** for
   `left`/`right`. The first orientation whose
   `clearance - (windowSpacing + triggerSpacing) >= relevantSize` wins. If none
   fits, fall back to the side with the **most** clearance seen so far; a final
   guard pins it to `bottom`.

4. **Tip class.** Remove the previous `tipClass`, then add
   `` `${tipClass}-${HUD.tipClasses[orientation]}` `` — the tip points *back* at
   the trigger, so `bottom → tip-top`, `top → tip-bottom`, `right → tip-left`,
   `left → tip-right`.

5. **Clamp the body.** Derive `maxHudBodyWidth/Height` from the chosen side's
   clearance (or the window for the cross-axis), floored by `minBodyWidth/Height`.
   If the body exceeds the max (or is under the min), set `hud.style.width` /
   `mainContainer.style.height` and switch on `overflow-x/y: scroll` when the
   measured content (`mainWidth/Height`) overflows.

6. **Place the HUD + tip.** For `top`/`bottom`: center the HUD horizontally on the
   trigger center, clamp `left` into `[windowSpacing, windowWidth - bodyWidth -
   windowSpacing]`, set `top` above/below the trigger by `triggerSpacing`, and set
   the tip's `left` to `within(triggerCenter - left - tipWidth/2, 0, bodyWidth -
   tipWidth)`. For `left`/`right`: the mirror, swapping axes. Near the corners,
   shrink the adjacent border radius to `2px` (legacy nicety) so the tip doesn't
   collide with a rounded corner.

The tip's **edge** placement (which side of the HUD it sits on) is the CSS
orientation class's job; the JS only sets the perpendicular offset along that edge
(and clears the inline value on the other axis).

## 4. Scroll-follow / reposition machinery

- `updateSizeAndPosition(force?)` is the throttled entry point. When `force === true`
  (on show) it always schedules; otherwise it calls `updateRecords()` and only
  schedules when something changed **and** a pass isn't already pending
  (`updatingSizeAndPosition`). It schedules `updateSizeAndPositionInternal` via the
  native `requestAnimationFrame` re-export (`updatingSizeAndPosition` is cleared at
  the end of the internal pass).
- `updateRecords()` re-reads window size, scroll-container scroll, the main
  element's outer size, and — when the trigger's `getScrollParent` differs from the
  global scroll container — that scroll parent's size/scroll, returning whether any
  changed. This is the change-detection that keeps the scroll/resize handlers cheap.
- Listeners wired at construction: `window` `resize`, the scroll container's
  `scroll` (skipped for a fixed trigger), and — when `listenToMainResize` — the
  main element's synthetic `resize`. `FieldLayoutDesigner` passes
  `listenToMainResize: false`.

## 5. jQuery removals → modern utils

| Legacy (`HUD.js`) | Modern replacement |
| --- | --- |
| `$(trigger)` | `getElement(trigger)` (→ a single `HTMLElement`) |
| `$('<div/>', {class})` + `.appendTo()` | `document.createElement` + `appendChild` |
| `$.isPlainObject(bodyContents)` | `isPlainObject` (`utils/misc`) |
| `.data('hud', this)` | module-level `WeakMap<Element, HUD>` |
| `.css('position')` / `.offsetParent()` | `getComputedStyle().position` / `el.offsetParent` |
| `.scrollParent()` | `getScrollParent` (`utils/scroll`) |
| `$trigger.offset()` | inline `rect.top + window.scrollY` (jQuery `.offset()` parity) |
| `Garnish.getOffset($trigger)` | `getOffset` (`utils/dom`) |
| `.outerWidth()` / `.outerHeight()` | `getOuterWidth` / `getOuterHeight` (`utils/dom`) |
| `$body.width()` / `.height()` | `getContentWidth` / `getContentHeight` (rect − padding − border) |
| `Garnish.$win` / `$bod` / `$scrollContainer` | `win` / `bod` / `globals.scrollContainer` |
| `$win.scrollLeft()/scrollTop()` | `window.scrollX/Y` |
| `$scrollContainer.scrollTop()` | `_scrollContainerScrollTop()` (window → `scrollY`, else `el.scrollTop`) |
| `.find(':focusable')` | `getFocusableElements` (`utils/focusable`) |
| `getKeyboardFocusableElements($hud)` | `getKeyboardFocusableElements` (`utils/focus`) |
| `Garnish.focusIsInside($hud)` | `focusIsInside` (`utils/focus`) |
| `Garnish.within(...)` | `within` (`utils/misc`) |
| `Garnish.requestAnimationFrame` | the native RAF re-export (`utils/animation`) |
| `Garnish.uiLayerManager` | `getUiLayerManager()` (`managers/registry`) |
| `.velocity()` / `.show()` / `.hide()` | **n/a** — legacy HUD has no Velocity; show/hide are explicit `style.display = 'block' \| 'none'` toggles (`'block'`, not `''`, since `.hud` defaults to `display: none` — see doc 14) |
| `$header.insertBefore($mainContainer)` / `insertAfter` | `mainContainer.before(header)` / `.after(footer)` |

## 6. Testing strategy

happy-dom has no layout (`getBoundingClientRect()`/`offset*`/`client*` are 0) and
its proxy blocks `defineProperty` on `clientWidth`, so the implementation reads the
body's content box via `getBoundingClientRect()` (mockable with `vi.spyOn`) rather
than `clientWidth`.

**Unit-testable (happy-dom, mocked rects):**

- Settings/defaults merge; construction (DOM tree, shade, `aria-expanded`,
  `instances`); the `(trigger, settings)` param shift and the full
  `(trigger, body, settings)` signature.
- `updateBody` header/footer extraction + `has-header`/`has-footer` flags.
- `show`/`hide`/`toggle` toggling, `show`/`hide`/`submit` events + the settings
  callbacks, `aria-expanded` flips, `activeHUDs` membership, `closeOtherHUDs`.
- Layer add/remove via a fresh `UiLayerManager`; the Escape shortcut
  (`manager.triggerShortcut`); shade-click hide.
- Focus: `Tab` on the trigger moves focus into the HUD; `hide` restores focus to
  the trigger when focus was inside (focusable children get `getClientRects`
  stubbed to look visible, as in the Modal/focusable tests).
- **Positioning math** with `getBoundingClientRect` mocked on the trigger + body
  and `windowWidth/Height` set directly: assert the chosen `orientation` and tip
  class for "room below" (`bottom`), "tall body against the left edge" (`right`),
  and the `orientations` preference order; assert `updateSizeAndPosition` fires and
  `hud.style.left/top` get set. `updateSizeAndPosition(true)` is asserted to defer
  to RAF (the animation module's RAF is mocked onto a manual queue + `flushRaf()`).
- `updateRecords` change detection; `destroy` cleanup.

**Playground-only (needs real layout/pointer/CSS):** the side actually flipping as
a trigger nears an edge, the tip rendering against the correct edge, the
scroll-follow loop tracking the trigger on real scroll/resize, body
overflow/`overflow: scroll` clamping, and the corner border-radius nicety. See
playground section 11 ("HUD — anchored popover").
