# Z-layers

Every surface in the CP that escapes normal flow picks a **rung** from a shared ladder instead of
inventing a number. The ladder is declared once, in two mirrored files:

| File | For |
| --- | --- |
| [`packages/craftcms-ui/src/styles/shared/z-layers.css`](../packages/craftcms-ui/src/styles/shared/z-layers.css) | CSS (`var(--c-z-modal)`). The source of truth. |
| [`packages/craftcms-ui/src/constants/z-layers.ts`](../packages/craftcms-ui/src/constants/z-layers.ts) | JS (`ZLayer.Modal`), for overlay configs and inline styles. |

`src/styles/z-layers.test.ts` asserts the two agree, so a rung added or moved in one has to be added
or moved in the other.

The stylesheet is imported by `@craftcms/ui/styles/cp.css`, which `resources/css/cp.css` pulls in,
which the CP's only layout (`resources/views/app.blade.php`) always loads. The tokens are therefore
available on every CP page, including inside shadow roots — custom properties inherit through the
shadow boundary.

## The ladder

### Local — within a component's own stacking context

| Token | `ZLayer` | Value | Use for |
| --- | --- | --- | --- |
| `--c-z-behind` | `Behind` | `-1` | Decorative fill painted behind its own content |
| `--c-z-base` | `Base` | `0` | Explicitly on the baseline — mostly to reset a lift |
| `--c-z-raised` | `Raised` | `1` | Lifted above sibling content (a check overlay, a focus ring) |
| `--c-z-floating` | `Floating` | `2` | Above a sibling that's already raised |
| `--c-z-sticky` | `Sticky` | `10` | Sticky headers/footers/toolbars inside a scroll container |

### Page-level — competing with the rest of the CP

| Token | `ZLayer` | Value | Use for |
| --- | --- | --- | --- |
| `--c-z-page-header` | `PageHeader` | `2000` | Sticky page and editor headers |
| `--c-z-nav` | `Nav` | `2100` | Persistent CP chrome — the global sidebar |
| `--c-z-drag` | `Drag` | `3000` | Drag helpers and drop indicators |
| `--c-z-slideout-shade` | `SlideoutShade` | `4000` | The shade behind a slideout |
| `--c-z-slideout` | `Slideout` | `4100` | Slideout panels and their container |
| `--c-z-modal-shade` | `ModalShade` | `5000` | The shade behind a modal |
| `--c-z-modal` | `Modal` | `5100` | Modal panels |
| `--c-z-overlay` | `Overlay` | `6000` | Menus, comboboxes, selects, popovers, HUDs |
| `--c-z-notification` | `Notification` | `7000` | Toasts and notifications |
| `--c-z-tooltip` | `Tooltip` | `8000` | Tooltips |
| `--c-z-debug` | `Debug` | `9000` | Dev-only chrome (the debug toolbar) |

Rungs are spaced by 1000 (100 within a shade/panel pair) so a new layer can be slotted between two
existing ones without renumbering anything.

## Rules

**Pick local unless the thing is attached to `<body>`.** A page-level rung only wins if no ancestor
has created a stacking context, so using one from inside a component works right up until somebody
adds a `transform`, an `opacity`, or a `filter` above it. If the surface renders in place, it's
local; if it's teleported, portalled, or appended to `<body>`, it's page-level.

**Anchored overlays sit above modals on purpose.** `--c-z-overlay` is above `--c-z-modal` because an
overlay is opened *from* a surface and has to paint above whichever surface opened it — a menu inside
a modal is ordinary, and there's no reliable way for the menu to know what it was opened from.
`--c-z-tooltip` is highest for the same reason: anything at all can have a tooltip.

**Don't add a rung for one component.** Reach for `--c-z-raised` / `--c-z-floating` first. A new rung
is warranted only when a surface genuinely has to be ordered against other page-level surfaces.

## What the ladder doesn't cover

### The top layer

`craft-dialog` is a Lion modal dialog, which opens with `HTMLDialogElement.showModal()`. Top-layer
content paints above every z-indexed element regardless of the number, so **no rung will ever cover
it**, and ordering *between* top-layer elements is order-of-entry rather than z-index.

Lion's non-modal overlays (`craft-popover` and everything built on it, `craft-tooltip`,
`craft-select-rich`, `craft-combobox`) use a `<dialog>` too, but open it non-modally — those stay
z-indexed and are on the ladder. Lion writes the value inline on its wrapping `<dialog>`, so it can't
be styled from a stylesheet; each component passes it through `_defineOverlayConfig()`:

```ts
override _defineOverlayConfig() {
  return {...super._defineOverlayConfig(), zIndex: ZLayer.Overlay};
}
```

A new Lion-based overlay that forgets this inherits Lion's default of `9999`, which lands above every
rung — visibly wrong only in that it out-stacks tooltips.

### The legacy CP bundle

`packages/craftcms-legacy/cp/src/css` still uses raw numbers, topping out at `1001` (`.prompt`, the
login screen). That's why the page-level band starts at `2000`: every rung clears legacy without
legacy having to be renumbered first, and the two stacks can share a page — which they do on any
`CpScreenResponse` screen, where the Inertia shell wraps PHP-rendered inner HTML.

Roughly, legacy occupies:

| Legacy value | What | Ladder equivalent |
| --- | --- | --- |
| `99` | `.craft-tooltip` | `--c-z-tooltip` |
| `100`/`101` | Garnish HUDs and modals, `#notifications`, datepicker/timepicker, selectize dropdowns, live preview | `--c-z-overlay`, `--c-z-notification`, `--c-z-modal` |
| `1000` | `.progressbar` | `--c-z-page-header` |
| `1001` | `.prompt`, login | `--c-z-modal` |
| `1000000` | chart tooltips | `--c-z-tooltip` |

Migrating those is deliberately **not** part of the ladder's introduction: the legacy CSS is a
prebuilt webpack bundle whose ordering is load-bearing for surfaces that no longer have tests, and
its numbers are internally consistent with each other. Port a legacy surface onto the ladder when you
port the surface itself.

`@craftcms/garnish` is standalone and can't import `@craftcms/ui`, so `Drag`'s `helperBaseZindex`
default repeats `--c-z-drag`'s value (`3000`) rather than referencing it.
