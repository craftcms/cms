/** A trail short enough that its current page never truncates when collapsed. */
export const SHORT_TRAIL = `
  <craft-breadcrumb-item href="#root">Site Name</craft-breadcrumb-item>
  <craft-breadcrumb-item href="#entries">Entries</craft-breadcrumb-item>
  <craft-breadcrumb-item href="#type">Entry Type</craft-breadcrumb-item>
  <craft-breadcrumb-item href="#parent">Parent Entry</craft-breadcrumb-item>
  <craft-breadcrumb-item>Current</craft-breadcrumb-item>`;

/** The gap, in pixels, between the trail and the control after it. */
export const ROW_GAP = 8;

/**
 * A row like a page header's: the trail sits in a wrapper sized by its
 * content, followed by another control.
 */
export const besideOtherContent = (trail = SHORT_TRAIL) => `
  <div data-row style="display: flex; align-items: center; gap: ${ROW_GAP}px">
    <div data-wrapper style="flex: 0 1 auto; min-inline-size: 0">
      <craft-breadcrumbs>${trail}</craft-breadcrumbs>
    </div>
    <button type="button" data-after>Actions</button>
  </div>`;
