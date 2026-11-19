---
paths:
  - 'resources/js/**'
  - 'resources/css/**'
  - 'resources/templates/**'
  - 'packages/craftcms-ui/**'
  - 'packages/craftcms-legacy/**'
---

# Right-to-left layouts

The control panel runs right-to-left for languages such as Arabic and Hebrew. Anything with a side, a direction or an end has to work both ways.

## Use logical properties
Use `inline-start`/`inline-end`, `margin-inline`, `inset-inline-*` and the like. A physical value, such as `left`, `right`, `scale: -1 1` or a `translate` along x, needs an RTL counterpart (a `:dir(rtl)` rule), or a reason it means the same on both sides.

## Mirror overlay placements
Popper's placements are physical: `bottom-start` lines up with the anchor's left edge whatever the direction. `craft-popover`, `craft-tooltip` and the overlay dropdown pass theirs through `placementForDirection()` from `packages/craftcms-ui/src/utilities/overlay-layer.ts`, so a menu opens toward the start of the line and side placements swap sides. Anything else that calls Popper has to do the same.

## Name arrows and chevrons for the way they point
`craft-icon` doesn't mirror arrows or chevrons. Pick the name for the direction you mean, with `isRtl()` from `resources/js/common/craft/direction.ts` (or the element's computed `direction` in `@craftcms/ui`), as the sidebar toggle and reorder buttons do. A chevron that the component draws itself, like a nav flyout's, can be mirrored with a `:dir(rtl)` rule instead.

## Mirror icons that point a way
Some icons point without being arrows: the wrench, the external-link arrow, checkmarks and the magnifying glass. `craft-icon` mirrors these in RTL, under their aliases too. Add a new one to that list in `icon.ts`, rather than flipping it where it's used.

## Let text-bound spacing follow the text
Spacing that offsets something in the text, such as uneven padding that balances letter spacing after the last letter, follows the text's direction, not the page's. Give such a label `dir="auto"`, as `EditionBadge.vue` does, so a Latin name in an RTL page keeps its LTR padding.

## Check it in RTL
Set `dir="rtl"` on `<html>`, or switch the user's language to Arabic, and look at the change. Cover RTL behavior in a browser test with a `dir="rtl"` ancestor. For an overlay, leave room on both sides of its anchor: when the wrong side doesn't fit, Popper's flip moves it to the other, hiding the bug.
