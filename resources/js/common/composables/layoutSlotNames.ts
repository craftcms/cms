/**
 * The outlets a screen shell exposes to `<LayoutSlot>`. Their names are public
 * API — plugins fill them from their own pages through the global
 * `craft:layout-slot` component — so renaming one is a breaking change.
 */
export const layoutSlotNames = [
  'context-menu',
  'content-sidebar',
  'subnav-actions',
  'error-summary',
  'content-toolbar',
  'content-toolbar-meta',
  'content-toolbar-actions',
  'title',
  'content-actions',
  'content-tabs',
  'content-notices',
  'content-details',
  'content-footer',
  'additional-buttons',
  'primary-action',
  'page-footer',
] as const;

export type LayoutSlotName = (typeof layoutSlotNames)[number];

export function isLayoutSlotName(name: string): name is LayoutSlotName {
  return (layoutSlotNames as readonly string[]).includes(name);
}
