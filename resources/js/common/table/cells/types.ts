/** An option in a select cell. `hint` is only shown by a rich select. */
export interface SelectCellOption {
  label: string;
  value: string;
  hint?: string | null;
  disabled?: boolean;
}

/**
 * Lion form controls announce their starting value too. Only a change the person
 * made should reach a cell's `change` listeners.
 */
export function isInitialization(event: Event): boolean {
  return event instanceof CustomEvent && Boolean(event.detail?.initialize);
}
