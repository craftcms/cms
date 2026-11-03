/**
 * One step's first rows, as the values they'd fill each mapped destination with, and for an
 * element step, whether each would create an element, update an existing one, or be skipped.
 */
export interface StepReview {
  columns: Array<{key: string; label: string}>;
  rows: Array<{
    row: number;
    /** Null for a step that doesn't import elements. */
    action: 'create' | 'update' | 'skip' | null;
    /** The element a row updates. */
    label: string | null;
    url: string | null;
    values: Record<string, string>;
  }>;
  created: number | null;
  updated: number | null;
  /** Rows the step's matching leaves out. */
  skipped: number | null;
  total: number;
  error: string | null;
}

/** An enabled step, with what the plan's review works out for it. */
export interface ReviewStep {
  uid: string | null;
  number: number;
  label: string;
  source: string | null;
  preview: StepReview;
}
