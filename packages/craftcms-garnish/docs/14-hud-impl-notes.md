# 14 — `HUD` Implementation Notes

> Status: **Implemented.** Built against `13-hud-design.md` on top of `Base` (docs
> 01/05), the `UiLayerManager`, and the focus/dom/scroll utils that `Modal` (doc 03)
> already uses. `HUD` is **Phase 3** of the migration plan and the last
> `FieldLayoutDesigner` overlay blocker. All gates green in the worktree:
> `check:types`, `check:format`, `test` (**286 tests**, was 247 — +39), `build`,
> `playground:build`. `dist/index.js` jQuery references: **0**
> (`grep -ciE "jquery|\$\(" dist/index.js`).

## What shipped

| File | Change |
| --- | --- |
| `src/hud.ts` | **NEW.** `class HUD extends Base<HUDSettings>` — anchored popover, 4-way positioning, tip, scroll-follow, focus cycle, layer + Esc. |
| `src/index.ts` | Named exports `HUD`, `HUDSettings` (+ `HUDOrientation`, `HUDBodyContents` types); added `HUD` to the `Garnish` namespace object. |
| `tests/hud.test.ts` | **NEW.** 39 tests: settings/defaults, construction + param-shift, updateBody, show/hide/toggle + events, layer/Esc/shade, submit, focus, positioning math (mocked rects), updateRecords, destroy. |
| `playground/{index.html,main.ts,styles.css}` | **NEW** section 11 "HUD — anchored popover": five edge-anchored triggers, event logging, HUD/tip CSS. |
| `README.md`, `docs/06-api-reference.md` | HUD marked **supported**; reference entry + usage example; overlay set noted complete. |

## Exact exported public API

```ts
export type HUDOrientation = 'top' | 'bottom' | 'left' | 'right';
export type HUDBodyContents =
  | string | Node | Node[] | NodeListOf<Node> | null | undefined;

export interface HUDSettings extends GarnishBaseSettings {
  shadeClass: string; hudClass: string; tipClass: string; bodyClass: string;
  headerClass: string; footerClass: string; mainContainerClass: string; mainClass: string;
  orientations: HUDOrientation[];
  triggerSpacing: number; windowSpacing: number; tipWidth: number;
  minBodyWidth: number; minBodyHeight: number;
  withShade: boolean;
  onShow: HUDCallback; onHide: HUDCallback; onSubmit: HUDCallback;
  closeBtn: ElementInput | null;
  listenToMainResize: boolean; showOnInit: boolean; closeOtherHUDs: boolean;
  hideOnEsc: boolean; hideOnShadeClick: boolean;
}

export class HUD extends Base<HUDSettings> {
  static instances: HUD[];
  static activeHUDs: Record<string, HUD>;
  static readonly tipClasses: Record<HUDOrientation, string>;
  static readonly defaults: HUDSettings;

  $trigger; $fixedTriggerParent; $hud; $tip; $body; $header; $footer;
  $mainContainer; $main; $shade; $nextFocusableElement;   // HTMLElement | null
  showing: boolean; orientation: HUDOrientation | null; tipClass: string | null;
  windowWidth; windowHeight; scrollTop; scrollLeft; mainWidth; mainHeight;
  spWidth; spHeight; spScrollTop; spScrollLeft;            // number | null

  constructor(trigger: ElementInput, bodyContents?: HUDBodyContents | Partial<HUDSettings>, settings?: Partial<HUDSettings>);

  updateBody(bodyContents: HUDBodyContents): void;
  show(ev?: Event): void;
  showContainer(): void;
  onShow(): void;
  updateRecords(): boolean;
  updateSizeAndPosition(force?: boolean | Event): void;
  updateSizeAndPositionInternal(): void;
  hide(): void;
  hideContainer(): void;
  onHide(): void;
  toggle(): void;
  submit(): void;
  onSubmit(): void;
  override destroy(): void;
}
export default HUD;
```

Private: `_handleSubmit`, `_scrollContainerScrollTop`, `_scrollContainerScrollLeft`,
`_setRadius`. Module-private helpers: `appendContent`, `jQueryOffset`,
`getContentWidth`, `getContentHeight`. Events: `show`, `hide`, `submit`,
`updateSizeAndPosition`, `destroy`.

## What the `FieldLayoutDesigner` consumer needs (the real contract)

`FieldLayoutDesigner.js` (~line 505) does:

```js
const hud = new Garnish.HUD(this.$addBtn, {
  hudClass: 'hud fld-library-hud cp-legacy',
  listenToMainResize: false,
  showOnInit: false,
  orientations: ['right', 'bottom', 'left'],
});
hud.on('show', () => { this.designer.$libraryContainer.appendTo(hud.$main); ... });
hud.on('hide', () => { this.$addBtn.focus(); });
this.$addBtn.on('activate', () => { hud.show(); });
```

The modern HUD covers all of it:

- **`new HUD(trigger, settings)` param shift** — the 2nd-arg plain object is treated
  as settings (no body contents). ✔
- **`hudClass` multi-class string** — assigned via `el.className`, so
  `'hud fld-library-hud cp-legacy'` lands as three classes. ✔
- **`listenToMainResize: false`, `showOnInit: false`, `orientations`** — honored. ✔
- **`hud.$main`** — a raw `HTMLElement` the consumer appends into. Under compat,
  jQuery `$(hud.$main)` / `.appendTo(hud.$main)` still works because jQuery accepts a
  DOM element. ✔
- **`hud.on('show'|'hide', …)`** + **`hud.show()`** — the event system + method. ✔
- **`$addBtn.on('activate', …)` → `hud.show()`** — the consumer wires that; the HUD
  also flips the trigger's `aria-expanded` on show/hide. ✔

## Deviations from legacy `HUD.js` (and why)

1. **No fade / no Velocity.** The shipped legacy `HUD.js` does **not** use Velocity —
   `showContainer`/`hideContainer` are jQuery `.show()`/`.hide()` (display toggles),
   and `showOnInit` just sets `opacity` 0→1 around the first positioning pass. The
   migration-plan table (doc 00) lists `.velocity()` as a HUD jQuery-removal, but
   that does not match the current source, so there is **no Velocity→WAAPI
   conversion** here. We port the display toggles to `style.display = ''` / `'none'`
   and keep the opacity-0-until-positioned flicker guard. (If a fade is wanted later,
   reuse `Modal._fade` — but that would be a new feature, not parity.)

2. **`$body.width()/.height()` → rect-minus-box, not `clientWidth`.** jQuery
   `.width()/.height()` is the content box. The port computes it from
   `getBoundingClientRect()` minus computed padding **and** border (`getContentWidth`/
   `getContentHeight`) rather than `clientWidth - padding`. Reason: identical result
   in a real browser, **and** it stays mockable in happy-dom, whose proxy throws on
   `Object.defineProperty(el, 'clientWidth', …)` but allows `vi.spyOn(el,
   'getBoundingClientRect')`.

3. **Setting body/HUD width via `style.width` directly.** Legacy `$hud.width(n)` /
   `$mainContainer.height(n)` go through jQuery's box-model-aware setters. The port
   sets `style.width`/`style.height` in px. For the CP's content-box HUD this matches;
   for a border-box HUD the clamp would be off by padding+border. This only affects
   the rarely-hit overflow-clamp branch and is layout-/playground-verified.

4. **Window size = `window.innerWidth/innerHeight`** (matching the `Modal` port),
   where legacy used `$(window).width()/.height()` (= `documentElement.clientWidth`,
   i.e. excluding the scrollbar). The difference is the scrollbar width (~15px) and
   only shifts the viewport-edge clamp slightly; consistent with `Modal`. Documented
   here rather than re-deriving the jQuery value.

5. **`tap,click` → `click` on the shade.** No `tap` synthetic event exists in the
   modern core (it's a touch alias). The shade dismiss binds `click`; on touch
   devices a tap dispatches `click`, so behavior is preserved.

6. **`activeHUDs` keyed by the instance namespace.** Legacy used `this._namespace` as
   the key in the `Garnish.HUD.activeHUDs` object; the port keeps that exactly
   (`HUD.activeHUDs[this._namespace]`). `destroy()` additionally `delete`s the
   `activeHUDs` entry (legacy did not) so `closeOtherHUDs` can never call `.hide()` on
   a destroyed instance — a small, safe hardening.

7. **`$.data('hud', this)` → `WeakMap`.** A module-level `WeakMap<Element, HUD>`
   (`hudElements`) mirrors `Modal`'s `containerModals`. Nothing in core reads it yet
   (legacy didn't either), but it's kept for parity and cleared in `destroy`.

8. **Fixed-ancestor walk uses native `offsetParent`.** `$parent.offsetParent()` +
   `nodeName !== 'HTML'` → a `while` over `el.offsetParent`, checking
   `getComputedStyle(parent).position === 'fixed'`. `offsetParent` returns `null` for
   detached/hidden trees, which terminates the loop naturally.

## happy-dom / testing caveats (for the next engineer)

- **No layout.** `getBoundingClientRect()`, `offset*`, and `client*` are all 0. The
  positioning tests `vi.spyOn(trigger, 'getBoundingClientRect')` and the body's rect,
  and set `hud.windowWidth/windowHeight` directly before calling
  `updateSizeAndPositionInternal()` (which reads those records, not `updateRecords`).
- **`defineProperty` on `clientWidth` throws** under happy-dom's proxy — hence
  deviation #2 (measure via the mockable `getBoundingClientRect`).
- **RAF is mocked onto a manual queue** (as in the drag tests) so
  `updateSizeAndPosition(true)` is observably *deferred*; `flushRaf()` runs the
  positioning pass. Calling `updateSizeAndPositionInternal()` directly skips the queue
  for the math assertions.
- **Focusable elements need visible layout boxes** — `getClientRects` is stubbed on
  focusable children (`makeVisible`) so `getFocusableElements`/`getKeyboardFocusableElements`
  see them, matching the Modal/focusable tests.
- **`<form>` proxy identity.** happy-dom hands back a *distinct* proxy when you reach
  a `<form>` via `parentElement`, so the tree-shape test asserts nesting with a
  selector from `$hud` (`form.body > .main-container > .main`) instead of node
  identity.

## Playground-only (NOT unit-tested — needs real layout/pointer/CSS)

- The side actually flipping as a trigger nears a viewport edge (the visible
  orientation pick), and the tip rendering against the correct edge.
- The scroll-follow loop tracking the trigger on real scroll/resize (and the
  fixed-trigger `position: fixed` branch).
- Body overflow → `overflow-x/y: scroll` clamping when content exceeds the clearance.
- The corner border-radius nicety near the tip.

See playground **section 11** ("HUD — anchored popover"): five triggers anchored near
different edges of a bounded arena, each toggling a HUD that logs
`show`/`hide`/`updateSizeAndPosition` (with the chosen orientation).
