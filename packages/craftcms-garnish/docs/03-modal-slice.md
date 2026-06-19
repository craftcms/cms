# Modal Slice — Dependency Footprint & PoC Contract

> Modal is the chosen representative component for the vertical-slice PoC. It is medium-complexity: focus management,
> sizing, animation, accessibility — but no recursive dependencies. This doc tells the build team exactly what core
> must provide, what jQuery removals Modal needs, and the public contract consumers rely on.

## 1. What core must provide for Modal (build checklist)

**Tier 1 — required:**
- `Base` class: constructor, `setSettings`, `on/off/once/trigger`, `addListener/removeListener/removeAllListeners`, `enable/disable`, `destroy()`, `_disabled` gate.
- Event system: instance pub/sub + DOM listener registry (per design doc 01 §2).
- Globals: `Garnish.bod`/`$bod`, `Garnish.win`/`$win`.
- Constants: `ESC_KEY` (27), `FX_DURATION` (200), `ltr`/`rtl` flags.
- `UiLayerManager` singleton: `addLayer(container)`, `removeLayer()`, `registerShortcut(keyCode, cb)`, `currentLayer`.
- ARIA/focus helpers: `addModalAttributes`, `hideModalBackgroundLayers`, `resetModalBackgroundLayerVisibility`,
  `trapFocusWithin`, `setFocusWithin`, `getFocusedElement`, `getKeyboardFocusableElements`.
- Focusable-element matcher (design doc 01 §4.E).
- `ResizeHandle` SVG export (trivial, from `icons/ResizeHandle.js`).

**Tier 2 — needed only for draggable/resizable modals:**
- `DragMove` (≈15-line `BaseDrag` subclass; updates `left`/`top` on drag).
- `BaseDrag` (large, ~580 lines). **PoC decision: default `draggable:false`/`resizable:false`; stub or omit BaseDrag
  for the slice.** Modal works fully without it at defaults.

## 2. jQuery removals inside Modal (13 sites)

| Use case | jQuery | Native replacement | Difficulty |
| --- | --- | --- | --- |
| Element creation | `$('<div/>')` | `document.createElement` | trivial |
| Insertion | `.appendTo()`, `.insertBefore()`, `.append(svg)` | `appendChild`/`insertBefore`/`innerHTML` | trivial |
| Class/attr/style | `.addClass/.removeClass/.attr/.css` | `classList`/`setAttribute`/`.style` | trivial |
| Dimensions | `.outerWidth/.outerHeight` | `offsetWidth`/`offsetHeight` | trivial |
| Window size | `Garnish.$win.width()/.height()` | `window.innerWidth/innerHeight` | trivial |
| Display | `.show()/.hide()` | `.style.display = 'block'/'none'` | trivial |
| Data storage | `.data('modal', this)` | WeakMap or `el._garnishModal` | trivial |
| Type check | `$.isPlainObject(container)` | plain-object check / `nodeType` test | trivial |
| **Animation** | **`.velocity('fadeIn'/'fadeOut'/'stop')`** | **CSS transitions or WAAPI (`el.animate`)** | **MEDIUM** |

**Animation decision:** Replace Velocity with the Web Animations API (`element.animate([{opacity:0},{opacity:1}], {duration})`)
or CSS `opacity` transitions + `transitionend`. Gate on `prefersReducedMotion`. `.velocity('stop')` becomes a no-op /
`anim.cancel()`. Shade fade ≈ 50ms, container fade ≈ `FX_DURATION` (200ms), matching legacy.

## 3. Public API contract (must stay identical for consumers)

**Constructor:** `new Garnish.Modal(container?, settings?)` — also accepts `new Garnish.Modal(settings)` (param shift
when first arg is a plain object).

**Default settings:** `autoShow:true, draggable:false, dragHandleSelector:null, resizable:false, minGutter:10,
onShow, onHide, onFadeIn, onFadeOut, closeOtherModals:false, hideOnEsc:true, hideOnShadeClick:true,
triggerElement:null, shadeClass:'modal-shade'`.

**Methods:** `show()`, `hide(ev?)`, `quickShow()`, `quickHide()`, `updateSizeAndPosition()`, `getWidth()`,
`getHeight()`, `destroy()`, `addLiveRegion()`, and overridable hooks `onShow/onHide/onFadeIn/onFadeOut`.

**Properties:** `visible`, `settings`, `desiredWidth`, `desiredHeight`, `$triggerElement`, `$container`, `$shade`,
`dragger`, `resizeDragger`. **Statics:** `Garnish.Modal.instances`, `Garnish.Modal.visibleModal`.

**Events triggered:** `show`, `hide`, `fadeIn`, `fadeOut`, `updateSizeAndPosition`, `escape`, `destroy`.

## 4. Real consumer usage (validate against these)

- Direct instantiation with settings (e.g. `new Garnish.Modal($container, {closeOtherModals:false})`).
- Subclassing via `Garnish.Modal.extend({ init, onShow, destroy }, { defaults })` — e.g. `Craft.DeleteUserModal`,
  `Craft.CpModal`, `BaseElementSelectorModal`, `PreviewFileModal`, `AssetImageEditor`. These rely on `this.base(...)`
  super-calls → **the compat layer must make `.extend()` + `this.base()` work against the modern `class Modal`.**
- Callback settings (`onShow`/`onHide`) and `.on('show'|'hide'|custom)` event binding.
- `draggable`/`resizable`/`dragHandleSelector` (Tier 2 — defer to post-PoC).

## 5. Modal's internal Garnish-class dependencies

- `DragMove` — only when `draggable:true`. Trivial subclass.
- `BaseDrag` — only when `resizable:true`. Large; defer for PoC.
- `UiLayerManager` singleton — required (ESC shortcut + layer stacking).

## 6. Flags for core team

- `getFocusedElement()` returns a DOM element in modern core (legacy returned a jQuery collection); compat wraps with `$()`.
- Focus trap + focusable matcher accessibility is the highest-risk area — budget tests.
- The `.field:visible` heuristic in `setFocusWithin` (cms#15245) must be preserved.
