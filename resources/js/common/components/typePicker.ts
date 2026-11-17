export interface TypePickerOption {
  value: string;
  label: string;
  /** Shown after the label when {@link showHint} is set, such as a handle. */
  hint?: string | null;
  showHint?: boolean;
  /** Options with the same group are listed together under it as a heading. */
  group?: string | null;
}
