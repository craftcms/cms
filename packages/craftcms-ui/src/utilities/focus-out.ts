/**
 * Whether a `focusout` leaves focus within any of `regions`.
 *
 * Focus in a shadow tree retargets to its host, so a region's own shadow
 * content counts as well as its light-DOM descendants. Focus moving to nothing
 * (`relatedTarget` is `null`, as when someone clicks text) isn't within a
 * region, so each caller decides what that means for it.
 */
export function focusStaysWithin(
  event: FocusEvent,
  ...regions: Array<Node | null | undefined>
): boolean {
  const moved = event.relatedTarget;

  return (
    moved instanceof Node &&
    regions.some((region) => region?.contains(moved) ?? false)
  );
}
