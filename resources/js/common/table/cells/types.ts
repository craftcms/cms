/** An option in a select cell. `hint` is only shown by a rich select. */
export interface SelectCellOption {
  label: string;
  value: string;
  hint?: string | null;
  disabled?: boolean;
}
