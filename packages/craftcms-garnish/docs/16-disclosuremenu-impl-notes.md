# 16 — `DisclosureMenu` Implementation Notes

> Status: **Implemented.** Built against `15-disclosuremenu-design.md` on top of
> `Base` (docs 01/05), the `UiLayerManager`, the focus/focusable/scroll/dom
> utils, and the WAAPI fade pattern `Modal` (doc 03) uses. `DisclosureMenu` is
> **Phase 3** of the migration plan and the largest UI component in the port
> (~1,008 legacy LOC). All gates green in the worktree: `check:types`,
> `check:format`, `test` (**336 tests**, of which **49** are new in
> `tests/disclosure-menu.test.ts`), `build`, `playground:build`. `dist/index.js`
> jQuery references: **0** (`grep -ciE "jquery|\$\(" dist/index.js`).

## What shipped

| File | Change |
| --- | --- |
| `src/disclosure-menu.ts` | **NEW.** `class DisclosureMenu extends Base<DisclosureMenuSettings>` — disclosure dropdown: above/below + left/center/right positioning, keyboard nav + type-ahead, focus management, layer + Esc, outside-click dismissal, optional search input, item/group builders. |
| `src/index.ts` | Named exports `DisclosureMenu`, `DisclosureMenuSettings` (+ `DisclosureMenuItem`, `DisclosureMenuItemConfig` types); added `DisclosureMenu` to the `Garnish` namespace object. |
| `tests/disclosure-menu.test.ts` | **NEW.** 49 tests across 12 describe blocks (see doc 15 §7). |
| `playground/{index.html,main.ts,styles.css}` | **NEW** section 12 "DisclosureMenu — dropdown menu": a static-markup actions menu + an API-built filterable menu with a search input; event + selection logging; menu CSS. |
| `README.md`, `docs/06-api-reference.md` | DisclosureMenu marked **supported**; reference entry + usage example; Phase-3 status updated. |

## Exact exported public API

```ts
export interface DisclosureMenuSettings extends GarnishBaseSettings {
  position: string | null; // 'below' forces below; else clearance picks
  windowSpacing: number;
  withSearchInput: boolean;
}

export interface DisclosureMenuItemConfig {
  type?: 'button' | 'link'; url?: string; id?: string;
  selected?: boolean; destructive?: boolean; disabled?: boolean;
  action?: string; icon?: string | Element | (() => Promise<Element> | Element);
  iconColor?: string; params?: string | Record<string, unknown>;
  confirm?: string; redirect?: string; attributes?: Record<string, string>;
  status?: string; label?: string; html?: string; description?: string;
  hidden?: boolean;
  onActivate?: (el: HTMLElement) => void; callback?: (el: HTMLElement) => void;
}
export type DisclosureMenuItem = DisclosureMenuItemConfig | Element;

export class DisclosureMenu extends Base<DisclosureMenuSettings> {
  static instances: DisclosureMenu[];
  static readonly defaults: DisclosureMenuSettings;
  static getInstance(el: Element | null | undefined): DisclosureMenu | undefined;

  $trigger; $container; $alignmentElement; $nextFocusableElement; $searchInput; // HTMLElement | null
  searchStr: string; clearSearchStrTimeout; infoIconActivated: boolean;

  constructor(trigger: ElementInput, settings?: Partial<DisclosureMenuSettings>);

  captureAlignmentElement(): void;
  addSearchInput(): void;
  addDisclosureMenuEventListeners(): void;
  focusElement(component: HTMLElement | 'prev' | 'next'): void;
  handleMousedown(event: MouseEvent): void;
  handleKeypress(ev: KeyboardEvent): void;
  isExpanded(): boolean;
  handleTriggerClick(): void;
  show(): void;
  hide(): void;
  focusIsInMenu(): boolean;
  setContainerPosition(): void;
  clearSearchStr(): void;
  isPadded(tag?: string): boolean;
  createItem(item: DisclosureMenuItem): HTMLLIElement;
  addList(): HTMLUListElement;
  addItem(item: DisclosureMenuItem, ul?: HTMLElement | null, prepend?: boolean): HTMLElement;
  addItems(items: DisclosureMenuItem[], ul?: HTMLElement | null): void;
  addHr(before?: Element | null): HTMLHRElement;
  getFirstDestructiveGroup(): HTMLUListElement | undefined;
  addGroup(heading?: string | null, addHrs?: boolean, before?: Element | null): HTMLUListElement;
  toggleItem(el: HTMLElement, show?: boolean): void;
  showItem(el: HTMLElement): void;
  hideItem(el: HTMLElement): void;
  toggleGroup(group: HTMLElement): void;
  removeItem(el: HTMLElement): void;
  updateHrVisibility(): void;  // @deprecated → updateVisibility
  updateVisibility(): void;
  hasVisibleItems(): boolean;
  override destroy(): void;
}
export default DisclosureMenu;
```

Module-private helpers: `getCraft`, `craftUrl`, `craftT`, `jQueryOffset`,
`getContentWidth`, `isVisibleItem`, `siblingsUntil`. Module state: `menuElements`
(trigger/container → instance `WeakMap`), `searchTextCache` (`<li>` → searchText
`WeakMap`), `itemIdCounter`. Events: `beforeShow`, `show`, `hide`, `destroy`.

## What the CP consumers need (the real contract)

`DisclosureMenu` is heavily used across the CP (~19 sites:
`NestedElementManager`, `EditableTable`, `BaseElementIndex`, `EntryIndex`,
`CustomizeSourcesModal`, `MatrixInput`, …). The two call shapes are:

```js
new Garnish.DisclosureMenu($menuBtn);
new Garnish.DisclosureMenu($trigger, settings);
```

and the consumers then drive it via:

- the **item/group builders** — `disclosureMenu.addItem({...})`,
  `.addItems(...)`, `.addGroup()`, `.addHr()`, `.removeItem(el)` — all ported. ✔
- the **instance lookup** — legacy `$el.data('disclosureMenu')`. The modern
  equivalent is the trigger/container `WeakMap` read via
  `DisclosureMenu.getInstance(el)`. Bridging the jQuery `.data('disclosureMenu')`
  *accessor* (so existing `$btn.data('disclosureMenu')` keeps resolving) is the
  **compat layer's** job, not this component's — out of scope for this port, but
  the instance is registered for both the trigger and the container so the bridge
  has something to read. ✔
- `.show()` / `.hide()` / `.isExpanded()` and the `show`/`hide` events. ✔

The modern public contract covers all of the above, so the legacy `.extend()`
path works once compat wraps the class (compat picks up any class on the
`Garnish` namespace automatically — doc, `compat.ts` §"Known core classes").

## Deviations from legacy `DisclosureMenu.js` (and why)

1. **Show is instant, hide fades (faithful) — but via WAAPI, not Velocity.** The
   legacy `show` does `velocity('stop').addClass('visible').css('opacity', 1)` (no
   animation), and `hide` does `velocity('fadeOut', {duration: FX_DURATION})`. The
   port keeps that asymmetry: show adds `visible` + `opacity: 1` instantly; hide
   animates opacity 1→0 over 200ms with `element.animate`, then strips `visible` +
   inline display. Gated on `prefersReducedMotion` / environments without
   `element.animate` (happy-dom), which finalize synchronously. `.velocity('stop')`
   → the tracked animation's `cancel()`.

2. **Hide reordered: bookkeeping before the fade.** Legacy starts the (async)
   `velocity('fadeOut')` *first*, then sets `aria-expanded=false`, restores focus,
   etc.; the velocity `complete` callback runs later. Because the WAAPI fade
   finalizes **synchronously** under happy-dom, the port does all the synchronous
   bookkeeping (aria, focus restore, listener/layer teardown, search clear, the
   `hide` event) **before** starting the fade. Behaviorally equivalent (the
   focus/aria work always ran before the panel was actually removed) and robust to
   the synchronous-finalize path.

3. **`Craft.*` via an optional global.** Legacy `createItem`/`addSearchInput`/init
   call `Craft.getUrl`, `Craft.t`, and `Craft.initUiElements` directly. The modern
   core is Craft-free, so these route through a `getCraft()` accessor
   (`globalThis.Craft`) with graceful fallbacks: `craftUrl(url)` returns the url
   unchanged when Craft is absent; `craftT(message)` returns the message; the
   slideout `initUiElements` is a no-op. In the CP (via compat) the real Craft is
   used; standalone (tests/playground) the fallbacks apply.

4. **`$(el).formsubmit()` omitted.** The legacy item builder calls jQuery's Craft
   `formsubmit` plugin to register action items. Calling it (or even *naming*
   jQuery) would re-introduce jQuery and fail the `dist/index.js` jQuery-free gate
   (the literal `jQuery` / `$(` strings are what the grep matches). The port still
   sets the `formsubmit` class + `data-action` / `data-form="false"` /
   `data-params` / `data-redirect` / `data-confirm` attributes, which is the markup
   the CP's global form-submit machinery keys off; only the explicit jQuery
   registration call is dropped. **Playground/CP-verified concern**, not unit
   tested.

5. **`$(el).data('searchText')` → `WeakMap`.** The per-`<li>` type-ahead text cache
   is a module-level `WeakMap<HTMLElement, string>` (`searchTextCache`) instead of
   jQuery data. Built identically (clone the `<li>`, strip `<svg>`s, lowercase +
   `trimStart`).

6. **Generated item ids use a counter, not `Math.random`.** Legacy ids are
   `menu-item-${Math.floor(Math.random() * 1000000)}`. The port uses a monotonic
   module counter (`menu-item-1`, `-2`, …) — deterministic and collision-free.

7. **The scroll-parent scroll listener is removed on hide.** Legacy `show` adds a
   `scroll` listener on the trigger's `scrollParent` but `hide` only removes the
   `$scrollContainer` + `$win` listeners (the scroll-parent one leaks until
   destroy). The port tracks `_scrollParent` and removes its listener on hide — a
   small, safe hardening (mirrors the HUD port's `activeHUDs` delete-on-destroy).

8. **`maxWidth` reset before measuring.** `setContainerPosition` clears any prior
   `maxWidth` clamp before measuring the panel's natural width, so a previous
   viewport-overflow clamp doesn't shrink the next measurement. Legacy left the
   stale `maxWidth` in place. Only affects the rarely-hit viewport-overflow branch.

9. **Window size = `window.innerWidth/innerHeight`** (matching the `Modal`/`HUD`
   ports), where legacy used `$(window).width()/.height()`
   (= `documentElement.clientWidth`, excluding the scrollbar). The difference is
   the scrollbar width (~15px) and only shifts the viewport-edge clamp slightly.

10. **No `optionselect` / `onOptionSelect`.** Selection is per-item (`onActivate` /
    `callback` on the item's `activate` event, then `hide`), exactly as in legacy
    `DisclosureMenu.js`. The menu-level `optionselect` event some CP code uses
    belongs to the separate legacy `MenuBtn`/`Menu` widgets, not `DisclosureMenu`.

## happy-dom / testing caveats (for the next engineer)

- **No layout.** `getBoundingClientRect()`, `offset*`, and `getClientRects()` are
  0/empty. The positioning tests `vi.spyOn(el, 'getBoundingClientRect')` and define
  `offsetWidth/Height` on the trigger + container (`mockRect`); the default
  happy-dom viewport (1024×768) is used as-is.
- **No `element.animate`.** The fade finalizes synchronously, so `hide()` is
  observable immediately — no RAF/animation mock is needed (unlike the HUD test,
  which mocks RAF for the deferred positioning pass; `DisclosureMenu` positions
  synchronously in `setContainerPosition`).
- **Focusable elements need visible layout boxes** — item buttons + the trigger
  get `getClientRects` stubbed (`makeVisible`) so `getFocusableElements` sees them,
  matching the Modal/HUD/focusable tests.
- **Item `activate`.** `addListener(el, 'activate', …)` installs the synthetic
  `activate` source; dispatching a plain `new CustomEvent('activate')` on the item
  invokes the handler. The subsequent `hide()` is scheduled `setTimeout(…, 1)`, so
  the selection tests use fake timers + `advanceTimersByTime(1)`.

## Playground-only (NOT unit-tested — needs real layout/pointer/CSS)

- The panel actually flipping above as the trigger nears the viewport bottom, and
  the left/center/right alignment near the edges.
- The scroll-follow reposition on real scroll/resize.
- The search-input filter visually collapsing empty groups / `<hr>`s / headings.
- The fade-out timing, and the `formsubmit` action-item submission (CP-only).

See playground **section 12** ("DisclosureMenu — dropdown menu"): a static-markup
actions menu (groups, a disabled item, a destructive item) and an API-built
filterable menu (`withSearchInput`, 10 items added via `addItems`), each logging
`beforeShow`/`show`/`hide` + the selected item.
