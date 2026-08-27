/**
 * The CP's stacking ladder, as numbers.
 *
 * A mirror of `styles/shared/z-layers.css`, which is the documented source of
 * truth — read that file for what each rung means and why the page-level band
 * starts at 2000. This copy exists for the places that can't reach a custom
 * property: Lion overlay configs (which write `z-index` inline onto the
 * wrapping `<dialog>`) and JS that sets `style.zIndex` directly.
 *
 * `z-layers.test.ts` parses the CSS and asserts the two agree, so a rung added
 * or moved in one file has to be added or moved in the other.
 */
export const ZLayer = {
  /* Local: within a component's own stacking context. */
  Behind: -1,
  Base: 0,
  Raised: 1,
  Floating: 2,
  Sticky: 10,

  /* Page-level: competing with the rest of the CP. */
  PageHeader: 2000,
  Nav: 2100,
  Drag: 3000,
  SlideoutShade: 4000,
  Slideout: 4100,
  ModalShade: 5000,
  Modal: 5100,
  Overlay: 6000,
  Notification: 7000,
  Tooltip: 8000,
  Debug: 9000,
} as const;

export type ZLayerKey = keyof typeof ZLayer;
export type ZLayerValue = (typeof ZLayer)[ZLayerKey];

/**
 * The custom property each rung is published as, e.g. `PageHeader` →
 * `--c-z-page-header`. Used by the sync test, and by anything that would rather
 * hand CSS a `var()` than a hard number.
 */
export function zLayerProperty(layer: ZLayerKey): string {
  return `--c-z-${layer.replace(/(?!^)([A-Z])/g, '-$1').toLowerCase()}`;
}
