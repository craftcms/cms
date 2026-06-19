# Garnish Migration Plan & Effort Estimate

> **Status: Authoritative plan — read this first.** This document is the go/no-go reference for converting the entire
> legacy Garnish library (`packages/craftcms-legacy/garnish/`) to the modern, jQuery-free TypeScript/ESM package
> (`packages/craftcms-garnish/`, published as `@craftcms/garnish`). It synthesizes the four design docs in this folder
> (01 core design, 02 dependency graph, 03 Modal slice, 04 scaffold) plus a source sanity-check of the hard modules,
> and turns them into a phased plan, a per-module effort table, and a cutover strategy.
>
> Companion docs: **01** is the core contract, **02** is the dependency/jQuery analysis, **03** is the Modal PoC
> template, **04** is the package toolchain. This doc references them rather than repeating them.

---

## 1. Executive summary

We are rebuilding Garnish as two layers shipped from one package. The **modern core + components** (`@craftcms/garnish`)
is a clean ESM/TypeScript rewrite: native `class extends` instead of Dean Edwards `Base.extend()`, a hand-written
emitter instead of `jQuery.on/off/trigger`, native DOM APIs instead of jQuery collections, and the Web Animations API
instead of Velocity. It is tree-shakeable, zero-runtime-dependency, and the surface every new Craft feature should
target. A **separate, opt-in compatibility layer** (`@craftcms/garnish/compat`) restores the entire legacy authoring
contract — `Garnish.Base.extend(instance, static)`, `init()`-as-constructor, `this.base()` super-calls, jQuery-collection
arguments (`$container`), namespaced jQuery-style events, `window.Garnish`, and `isJquery` — by mechanically wrapping the
modern classes. A plugin author keeps working unchanged by importing compat; they "drop it" by moving to the modern
constructor/`extends` API at their own pace. Nothing forces a flag-day rewrite of the ~107 consumer files.

The two packages **coexist**: the legacy `garnish` webpack bundle keeps shipping the jQuery library to the legacy CP
surface until the modern package reaches parity, and the modern package is adopted incrementally — first by the Inertia/Vue
CP and new code, then by legacy modules wrapped through compat. The legacy webpack `garnish` build is retired only at the
final cutover, once every consumer resolves `Garnish` from the new package (directly or via compat) and visual-regression
parity is proven on the real CP.

The strategy is deliberately **leaves-first** (per doc 02's topological order): foundation core first, then the drag
system and overlays, then selection/menus, then form inputs, then cutover. The two load-bearing risks are (a) the compat
layer faithfully reproducing `.extend()` + `this.base()`, and (b) the focusable-element matcher and animation parity. Both
are addressed below with explicit mitigations and test budgets.

---

## 2. The compat layer design (`@craftcms/garnish/compat`)

The compat layer is the single biggest determinant of "easy upgrade path." It must let an unmodified legacy module —
`Craft.DeleteUserModal = Garnish.Modal.extend({ init, onShow, destroy }, { defaults })` — run against the modern
`class Modal`. It is **opt-in**: importing it has side effects (it populates `window.Garnish` and wraps every class), and
not importing it gives you the clean modern surface with zero legacy weight.

### 2.1 How a plugin author adds or drops it

| Scenario | What they do |
| --- | --- |
| **Legacy plugin, no changes** | `import '@craftcms/garnish/compat';` once at bundle entry. This assigns `window.Garnish` with `.extend()`-able, jQuery-shaped classes. All existing `Garnish.X.extend({...})` and `$container` code keeps working. (Or: keep consuming the legacy webpack `garnish` global until it is retired — see §5.) |
| **Incremental migration** | Author migrates one class at a time from `Garnish.Modal.extend({init(){...}})` to `class extends Modal { constructor(){ super(); ... } }`, importing the modern named export `import {Modal} from '@craftcms/garnish'`. Compat and modern can be mixed in the same bundle during transition. |
| **Fully modern** | Drop the `import '@craftcms/garnish/compat'` line entirely. Tree-shaking then removes the compat code and the `window.Garnish` global. |

### 2.2 The wrapping mechanism — `compatify(ModernClass)`

The core of the compat layer is a single higher-order function that takes a modern ES class and returns a legacy-shaped
constructor that supports `.extend(instance, static)`. Per doc 01 §1.3, the modern core keeps plain constructors and
stable method names precisely so this can be mechanical.

```ts
// compat/extend.ts  (sketch — illustrative, not final code)
type ModernCtor = abstract new (...args: any[]) => object;

interface LegacyCtor {
  new (...args: any[]): any;
  extend(instance?: object, statics?: object): LegacyCtor;
}

function compatify(Modern: ModernCtor): LegacyCtor { /* returns Base-shaped ctor */ }
```

It restores each legacy behavior as follows:

- **`.extend(instance, static)` → a subclass.** `compatify` returns a constructor exposing a static `extend(instance,
  static)` that builds a new subclass of the modern class. `instance` members are copied onto the subclass prototype;
  `static` members onto the constructor. This reproduces Dean Edwards `lib/Base.js` semantics (the version-1.1a file we
  inspected) but backed by a real prototype chain instead of `new this()` prototype cloning.

- **`init` → constructor.** Legacy `Garnish.Base`'s constructor calls `this.init.apply(this, arguments)`. The modern core
  dropped that trampoline (doc 01 §1.2). The compat subclass therefore defines a `constructor(...args)` that calls
  `super(...args)` and then, if the `instance` object supplied an `init`, invokes `this.init(...args)`. Subclass authors
  keep writing `init`, never `constructor`.

- **`this.base(...)` synthesis.** This is the subtle part. In `lib/Base.js` the `extend` wrapper detects `/\bbase\b/` in a
  method's source and, at call time, sets `this.base = <ancestor method>`, runs the override, then restores the previous
  `this.base`. The compat layer reproduces this exactly: when copying an `instance` method that references `base`, wrap it
  so that on entry it sets `this.base` to the **prototype method it is overriding** (found by walking the modern prototype
  chain, i.e. `Object.getPrototypeOf(subclassProto)[name]`), bound to `this`, and restores the prior `this.base` on exit
  (try/finally for re-entrancy). The `/\bbase\b/` source-sniff and the save/restore dance are copied verbatim — they are
  observable and re-entrant (nested super-calls rely on the restore). For TypeScript-authored migrators we recommend
  `super.method()` instead; `this.base` is a compat-only affordance.

- **jQuery-collection args (`$container`, `$trigger`, `items`).** Modern constructors accept `Element | EventTarget |
  string | null`. Compat wraps the constructor to coerce any incoming jQuery collection (or selector string) to the native
  input the modern class expects (`jq[0]` / `Array.from(jq)`), and conversely exposes legacy `$`-prefixed properties
  (`this.$container`, `this.$trigger`, `this.$shade`) as jQuery wrappers around the modern native references via a getter.
  This requires jQuery as a **peer dependency of the compat entry only** (declared per doc 04 in `package.json`
  `peerDependencies` and `tsdown` `deps.neverBundle`); the modern entry stays jQuery-free.

- **`window.Garnish`.** Compat builds the legacy-shaped global by taking the modern `Garnish` namespace object (doc 01
  §6.1), running `compatify` over each class (`Base`, `Modal`, `HUD`, ...), re-adding the jQuery-only members (`$win`,
  `$doc`, `$bod`, `$scrollContainer`, `isJquery`, the `$.fn.activate/textchange/resize` chaining sugar, the deprecated
  `Menu`/`ShortcutManager` aliases), calling `initGarnish()` to attach the manager singletons, and assigning the result to
  `window.Garnish` under the same guard the legacy code uses (`if (typeof window.Garnish === 'undefined')`).

- **`isJquery`.** Dropped from core (doc 01 §4.B); restored in compat as `(v) => v instanceof jQuery`.

- **jQuery-style namespaced events.** The modern emitter already preserves the legacy event-string grammar verbatim
  (doc 01 §2.1 — space-split for pub/sub, comma-split for `addListener`, first-`.` namespace split) and the trigger-object
  key precedence (§2.3). So `on('show.myns', fn)` / `off('.myns')` and `addListener(el, 'click.foo', fn)` already behave
  identically; compat does **not** need to special-case them. Compat only adds the jQuery `$.event.special`
  `activate/textchange/resize` chaining sugar (`$el.activate(fn)`), routing it to the core `install*` functions (§3 of
  doc 01).

### 2.3 Compat layer effort

Treat compat as one substantial deliverable, not free. It needs its own Vitest suite asserting `.extend()`, `this.base()`
re-entrancy, `init` mapping, and `$`-property coercion against representative real subclasses (`Craft.CpModal`,
`BaseElementSelectorModal`). Budgeted at **8 dev-days** in the table (§4), front-loaded so the Modal PoC can be validated
through it.

---

## 3. Phased migration sequence

Ordered leaves-first using doc 02's topological order. Each phase is independently shippable (the package builds and the
already-migrated modules are usable) so we get continuous integration value rather than a big-bang merge.

### Phase 0 — Scaffold (DONE)

Package, tsdown dual-entry build, Vitest+happy-dom, Prettier all green (doc 04). `src/compat.ts` is a placeholder.

### Phase 1 — Core foundation + Modal PoC (the vertical slice)

The PoC proves the whole strategy end-to-end before we commit to the long tail.

| Module | Difficulty | Key jQuery removals | Risks | Deps |
| --- | --- | --- | --- | --- |
| `lib/Base.js` | n/a | none — replaced wholesale by native `class` (doc 01 §1) | none | — |
| `Base.js` → `base.ts` + `events.ts` + `dom-listeners.ts` | MED | `$.noop`/`$.extend`/`$.trim`/`$.proxy` trivial; **rebuild dual event system + namespaced DOM listeners without jQuery** | emitter/`off`-matching/`once`-wrapper parity; namespaced `.off` without jQuery namespaces | core |
| `Garnish.js` → `globals.ts`/`utils/*`/`custom-events/*`/`constants.ts` | HIGH | `$.event.special`→installers; `$win/$doc/$bod`→native; `.velocity()`→WAAPI; `.scrollParent()`→finder; `:focusable`→matcher; `$.data`→WeakMap | **focusable matcher fidelity**, animation parity, the 40+ util surface | Base |
| `focusable.ts` matcher (§4.E doc 01) | HIGH | reimplement jQuery-UI `:focusable`/`:visible` | accessibility correctness; underpins all focus traps | — |
| `EscManager.js`, `UiLayerManager.js` | LOW | `$.isPlainObject`, basic selectors, `$.each` | minimal | Base |
| `icons/ResizeHandle.js` | n/a | none (SVG string) | none | — |
| `Modal.js` → `modal.ts` | MED | per doc 03 §2: element creation/insertion/class/dims trivial; **Velocity fade → WAAPI** | size/position math; focus trap; defaults parity | Base, UiLayerManager, (DragMove/BaseDrag deferred) |
| **compat layer (initial)** | HIGH | `compatify`, `init`/`this.base`, `$`-coercion, `window.Garnish`, `isJquery`, event sugar (§2) | re-entrant `this.base`; jQuery peer wiring | core + jQuery |

PoC defers `draggable`/`resizable` Modal (defaults are `false` per doc 03 §1 Tier 2), so `BaseDrag` is **not** required for
the slice. **"PoC done"** = modern `Modal` + core build, plus an unmodified `Craft.*Modal` subclass running through compat
on the real CP with visual + a11y parity.

### Phase 2 — Drag system

The drag cluster is self-contained below `Base`/`Garnish` and feeds Modal's resizable/draggable mode, DragSort, DragDrop.
Do it as a unit; the `.scrollParent()` + auto-scroll machinery (BaseDrag lines ~83–210, a `setInterval` window-scroll loop
keyed off `_scrollProperty`/`_scrollAxis`/`_scrollDist`) is shared and must be ported once into a reusable scroll-parent +
auto-scroll helper.

| Module | Difficulty | Key jQuery removals | Risks | Deps |
| --- | --- | --- | --- | --- |
| `BaseDrag.js` | HIGH | `.scrollParent()` ×2, `.offset()`, `$.data/removeData` (→WeakMap), `$.makeArray/inArray`, `.index()`, auto-scroll `setInterval` loop | scroll-parent finder correctness; pointer math; drag perf | Base, Garnish |
| `DragMove.js` | LOW | none (already pure) | none | BaseDrag |
| `Drag.js` | MED | `.clone()`→`cloneNode(true)`, `.velocity()` return-to-source, `.outerWidth/Height`, `.offset()` | helper-clone positioning; return animation parity | BaseDrag |
| `DragDrop.js` | LOW | `$(el)`, class methods, `$.extend`, `$.noop` | minimal | Drag |
| `DragSort.js` | HIGH | `.insertBefore/After/prependTo`, `.offset()`, `.index()`, `.find/not/filter`, `$.contains` | `_getClosestItem` hit-detection algorithm + midpoint caching (perf-critical, large lists); insertion visual feedback | Drag |

### Phase 3 — Overlays & menus

| Module | Difficulty | Key jQuery removals | Risks | Deps |
| --- | --- | --- | --- | --- |
| `HUD.js` | MED-HIGH | `.scrollParent()`, `.offset/outerW/H`, `.velocity()`, insertion, `:focusable` | smart 4-way positioning/orientation logic; scroll-follow | Base, UiLayerManager, focusable |
| `CustomSelect.js` | MED | `.velocity()`, `.offset/scrollTop/Left`, positioning, `$.data` | anchor-relative positioning (`_alignLeft/Right/Center`) | Base, UiLayerManager |
| `SelectMenu.js` | LOW | `$.extend` | thin `.sel` wrapper | CustomSelect |
| `MenuBtn.js` | LOW-MED | `.data/.attr`, class, `.contains`, MutationObserver | keyboard search/nav | Base, CustomSelect |
| `ContextMenu.js` | MED | `$('<div/>')` etc. DOM creation, `.css`, `.mousedown`, `.show/.hide` | dynamic menu build | Base, UiLayerManager |
| `DisclosureMenu.js` | HIGH | `.velocity()` show/fadeOut, `:focusable` (6 sites), `.scrollParent()`, `.find/closest/filter/parent`, search `data('searchText')` | 1,008 LOC; complex focus + type-ahead search + positioning | Base, UiLayerManager, focusable |

### Phase 4 — Selection & form inputs

| Module | Difficulty | Key jQuery removals | Risks | Deps |
| --- | --- | --- | --- | --- |
| `Select.js` | HIGH | 41 jQuery sites: `.find/filter/index/not/eq/slice`, `.offset/outerW/H`, `$.data` (`select`/`select-handle`/`select-item`), `$.inArray/makeArray`, `:focusable` | highest coupling; `getClosestItem` spatial query; shift-range + Ctrl+A + keyboard; `$.data` item-back-references → WeakMap | Base, Garnish, focusable |
| `NiceText.js` | LOW-MED | `.val/.attr/.css`, `.height/.width`, `.velocity()` fade, insertion | text-measurement `getHeightForValue`; `textchange` event | Base, custom-events |
| `MixedInput.js` | LOW | `.attr/.val/.css/.prop`, insertion, `$.inArray` | caret positioning; nested `TextElement` | Base |
| `CheckboxSelect.js` | LOW | `.find/filter/not`, `.prop` | minimal | Base |
| `MultiFunctionBtn.js` | LOW | `.data/.find`, class | minimal; ARIA live region | Base |

### Phase 5 — Integration, cutover, retire legacy build

Wire `index.js` equivalent (`initGarnish()`), finalize the compat `window.Garnish`, run full visual-regression + a11y +
the existing legacy Garnish test suite against the new package, then retire the legacy webpack `garnish` bundle (§5).

---

## 4. Per-module effort estimate

Estimates are **dev-days for an engineer fluent in the codebase**, including unit tests but **excluding** the cross-cutting
visual-regression/QA pass (counted once in Phase 5). "Size" is legacy LOC. Difficulty drivers are honest about the hard
ones: the focusable matcher, Velocity→WAAPI, `.scrollParent()`/auto-scroll, `$.event.special`, and the two spatial hit-test
algorithms (`Select.getClosestItem`, `DragSort._getClosestItem`).

| Module | LOC | Difficulty | Est. (dev-days) | Notes |
| --- | --- | --- | --- | --- |
| `lib/Base.js` | 160 | — | 0 | Deleted; replaced by native class |
| `Base.js` (→ base/events/dom-listeners) | 193 | MED | 4 | Dual event system + namespaced DOM listeners are the real work, not the `$.x` swaps |
| `Garnish.js` (utils/globals/constants) | 1,211 | HIGH | 6 | 40+ utils; excludes matcher + custom-events (separate rows) |
| **focusable matcher** (§4.E) | — | HIGH | 3 | Single biggest reimplementation risk; heavy test budget |
| **custom-events** (activate/textchange/resize) | — | HIGH | 3 | `$.event.special`→installers + shared ResizeObserver |
| **animation utils** (shake, scrollContainerToElement) | — | MED | 2 | Velocity→WAAPI; scroll-parent finder lives here too |
| `EscManager.js` | 55 | LOW | 0.5 | Pure JS already |
| `UiLayerManager.js` | 181 | LOW | 1.5 | Light jQuery |
| `icons/ResizeHandle.js` | 4 | — | 0 | SVG string |
| `Modal.js` | 451 | MED | 4 | PoC reference; Velocity→WAAPI is the only hard bit |
| `BaseDrag.js` | 583 | HIGH | 6 | `.scrollParent()` + auto-scroll loop + WeakMap data; foundational for drag |
| `DragMove.js` | 15 | LOW | 0.25 | Trivial |
| `Drag.js` | 462 | MED | 3.5 | Clone helpers + return animation |
| `DragDrop.js` | 116 | LOW | 1 | — |
| `DragSort.js` | 697 | HIGH | 6 | `_getClosestItem` perf + insertion feedback |
| `HUD.js` | 764 | MED-HIGH | 5 | 4-way positioning |
| `CustomSelect.js` | 333 | MED | 3 | Anchor positioning |
| `SelectMenu.js` | 83 | LOW | 0.5 | Thin wrapper |
| `MenuBtn.js` | 444 | LOW-MED | 3 | Keyboard search |
| `ContextMenu.js` | 171 | MED | 2 | DOM creation |
| `DisclosureMenu.js` | 1,008 | HIGH | 7 | Largest UI component; focus + type-ahead + positioning |
| `Select.js` | 1,018 | HIGH | 8 | Highest jQuery coupling + spatial query |
| `NiceText.js` | 343 | LOW-MED | 2.5 | Text measurement |
| `MixedInput.js` | 424 | LOW | 3 | Caret handling |
| `CheckboxSelect.js` | 97 | LOW | 1 | — |
| `MultiFunctionBtn.js` | 125 | LOW | 1 | — |
| **Compat layer** (`compatify`, `window.Garnish`, jQuery coercion) | — | HIGH | 8 | The load-bearing upgrade-path risk (§2) |
| **Integration + cutover** (init wiring, legacy-build retirement, full QA/visual-regression/a11y) | — | HIGH | 10 | Counted once; covers all phases |
| **Total** | ~9k | — | **~104 dev-days** | ≈ 21 engineer-weeks; ~5 calendar months solo, ~2.5–3 months for a pair |

Add **15–20% contingency** for parity surprises (drag pointer math, animation timing, focus edge cases), giving a planning
range of **~120–125 dev-days**.

---

## 5. Consumer migration & cutover

There are **~107 consumer files** using `Garnish.X.extend(...)` / `new Garnish.X(...)` across `packages/craftcms-legacy/`
and `resources/js/`. `Garnish.Base` alone has ~96 subclass usages (doc 02), so the consumer story *is* the compat story.

### 5.1 Two consumer paths

| Path | Who | What they do | Effort |
| --- | --- | --- | --- |
| **Do nothing (compat shim)** | The ~107 existing legacy modules | Resolve `Garnish` from compat (`import '@craftcms/garnish/compat'`, or keep the legacy global until retirement). `.extend()`, `this.base()`, `$container`, namespaced events all keep working unchanged. | ~0 per file |
| **Opt-in to modern API** | New code; modules being actively touched | Use named modern imports + `class extends` + `super()`; native element args; `super.method()` instead of `this.base()`. | Per-file, voluntary, incremental |

The contract is: **no consumer is forced to change to keep working.** Migration to the modern API is opportunistic
(touch-it-fix-it), not a scheduled mass rewrite.

### 5.2 Retiring the legacy webpack `garnish` build

1. **Coexist (Phases 1–4).** Legacy webpack keeps bundling the jQuery `garnish` library and assigning `window.Garnish`.
   The modern package is consumed by the Vue/Inertia CP and new code. Two `Garnish` implementations may briefly exist;
   they must not both claim `window.Garnish` — the legacy guard (`if (typeof window.Garnish === 'undefined')`) and the
   compat guard ensure last-write-wins is controlled, and during overlap **only one** owns the global (start with legacy,
   flip to compat at step 3).
2. **Parity gate.** Once all 23 modules are ported and the compat suite is green, stand up the compat `window.Garnish` in a
   branch and point the legacy CP at it instead of the webpack bundle.
3. **Flip the global.** Make `@craftcms/garnish/compat` the sole provider of `window.Garnish`; stop emitting the webpack
   `garnish` entry. Run the full regression battery (§5.3).
4. **Delete.** Remove `packages/craftcms-legacy/garnish/` source and its webpack entry. jQuery remains only as the compat
   peer dep until the long-tail consumers themselves drop jQuery.

### 5.3 Testing strategy

- **Unit (Vitest + happy-dom):** Per-module, written alongside each port. Heaviest budget on the focusable matcher, the
  emitter (`off`/`once`/precedence parity), and the two spatial algorithms. Compat gets its own suite asserting
  `.extend()`/`this.base()`/`init`/`$`-coercion against real subclasses.
- **Existing legacy Garnish test suite:** Run it against the compat layer as a behavioral oracle — it encodes current
  expected behavior and is the cheapest parity check we have.
- **Visual regression on the real CP:** The decisive gate. Drive the actual control panel (modals, HUDs, disclosure menus,
  drag-sort matrix fields, selection) and diff against legacy. Animation, positioning, and drag feedback are not unit-testable
  with confidence.
- **Accessibility:** Manual + automated keyboard/focus-trap/ARIA checks on every overlay and menu (focus management is the
  top a11y risk — §6).

---

## 6. Risks & mitigations

| Risk | Why it matters | Mitigation |
| --- | --- | --- |
| **Focusable matcher fidelity** (§4.E) | Underpins every focus trap, modal, menu. jQuery-UI `:focusable` has subtle rules (href links, disabled controls, contenteditable, tabindex, visibility). Divergence = broken keyboard a11y. | Model on the published jQuery-UI `:focusable` algorithm; dedicated exhaustive unit suite; manual keyboard QA on all overlays. Build it in Phase 1 so everything downstream shares one tested implementation. |
| **Animation parity (Velocity → WAAPI)** | 6 modules use `.velocity()` (Garnish shake, Modal/HUD/Drag/CustomSelect/DisclosureMenu fades + return-to-source). Timing/easing mismatches read as "feels off." `.velocity('stop')` semantics. | Standardize on `element.animate()`; match legacy durations (shade ~50ms, container ~`FX_DURATION` 200ms, doc 03 §2); `stop`→`anim.cancel()`; gate all on `prefersReducedMotion`. Visual-regression catches drift. |
| **Drag system complexity** | BaseDrag's `.scrollParent()` + `setInterval` auto-scroll loop and the `_getClosestItem`/`_updateInsertion` hit-detection are the most algorithmically dense, perf-sensitive code. WeakMap replaces `$.data` back-references. | Port scroll-parent + auto-scroll into one shared, tested helper. Preserve the midpoint-caching optimization. Perf-test DragSort on large matrix fields. |
| **Dual event systems** | Object pub/sub (incl. class-level `Garnish.on(Target,...)`) *and* namespaced DOM listeners must both be reproduced without jQuery, with exact `off`/`once`/precedence semantics (doc 01 §2). | The core spec already pins the grammar and precedence as compatibility warts; replicate `once`-as-wrapper and backward-splice `off`. Emitter is the most-tested unit. |
| **`this.base()` re-entrancy** (compat) | Nested super-calls rely on save/restore of `this.base`; getting it wrong silently breaks deep subclass chains (`Craft.CpModal` etc.). | Copy the `lib/Base.js` save/restore-in-try/finally verbatim; test multi-level `extend` chains explicitly. |
| **RTL** | `rtl`/`ltr` derive from `body.classList` and affect positioning (HUD, CustomSelect, DisclosureMenu). | Keep `rtl`/`ltr` flags in core globals; include RTL cases in positioning visual-regression. |
| **Accessibility (focus/ARIA)** | Modal background-layer hiding, focus trap/release, `setFocusWithin`'s `.field:visible` heuristic (cms#15245) are correctness-critical and easy to regress. | Preserve the documented heuristics verbatim; manual a11y QA gate before cutover. |
| **jQuery peer in compat** | Compat needs jQuery for `$`-coercion and `isJquery`; must not leak into the modern entry. | jQuery is a peer dep of the `./compat` entry only; tsdown `deps.neverBundle` (doc 04); verify modern `index.js` bundle has zero jQuery. |

---

## 7. Recommended milestones

| Milestone | Scope | "Done" means |
| --- | --- | --- |
| **M1 — PoC (vertical slice)** | Core foundation (Base/events/dom-listeners/globals/constants), focusable matcher, custom-events, EscManager, UiLayerManager, Modal, initial compat layer | Modern `Modal` builds and runs; an **unmodified** real `Craft.*Modal` subclass works through `@craftcms/garnish/compat` on the live CP with visual + a11y parity. Emitter + matcher unit suites green. This is the go/no-go gate. |
| **M2 — Core GA** | Harden core surface; full util parity; compat `window.Garnish` stands up the full namespace; legacy Garnish test suite passes against compat | New code can target modern API; compat is a drop-in for any module that uses only ported classes. Core API frozen. |
| **M3 — Component wave 1 (drag + overlays)** | BaseDrag, DragMove, Drag, DragDrop, DragSort, HUD, CustomSelect, SelectMenu, MenuBtn, ContextMenu, DisclosureMenu | Each ships with parity tests; DragSort perf-validated on large lists; overlays pass positioning + a11y visual-regression. |
| **M4 — Component wave 2 (selection + inputs)** | Select, NiceText, MixedInput, CheckboxSelect, MultiFunctionBtn | All 23 modules ported; full compat namespace complete; entire legacy test suite green against the new package. |
| **M5 — Cutover** | Flip `window.Garnish` to compat as sole provider; retire legacy webpack `garnish` bundle; delete legacy source | Legacy bundle removed; full CP visual-regression + a11y battery green; jQuery present only as the compat peer dep. Long-tail consumers migrate off compat opportunistically thereafter. |

---

## 8. Bottom line for the go/no-go

The strategy is sound and de-risked by the existing core design: the modern core deliberately preserves the legacy event
grammar, precedence, and method names so the compat layer can wrap classes **mechanically** rather than re-implementing
each one twice. The upgrade path for the ~107 consumers is genuinely "do nothing or migrate at will," which is the whole
point. The real cost and risk concentrate in a handful of places — the focusable matcher, animation parity, the drag
algorithms, and the `this.base()` synthesis — all called out above with explicit budgets and mitigations. Total effort is
**~104 dev-days (~120–125 with contingency)**, sequenced so the Modal PoC validates the entire approach before the long
tail is committed.
