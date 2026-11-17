/**
 * What takes focus for a related element: its item, when the list moves focus
 * between items, or else the first control in it, such as its checkbox.
 */
export function elementFocusTarget(
  container: HTMLElement | null,
  id: number
): HTMLElement | null {
  const item = container?.querySelector<HTMLElement>(
    `.elements-stage [data-id="${id}"]`
  );

  return (
    (item?.matches('[tabindex="0"]')
      ? item
      : item?.querySelector<HTMLElement>(
          'craft-checkbox, craft-button, button, a[href], [tabindex="0"]'
        )) ?? null
  );
}
