import type {SortItem} from '@/common/types';

export type ViewMode = {
  mode: 'table' | 'cards' | 'structure';
  title: string;
  icon: string;
  structuresOnly?: boolean;
  availableOnMobile?: boolean;
};

/** A source's persisted column overrides (visible columns + their order). */
export type SourceColumnState = {
  /** Columns the user has explicitly chosen to show, in selection order. */
  visible?: Array<string>;
  /** The user's column ordering for this source. */
  order?: Array<string>;
};

/**
 * @TODO should inlineEditing and static be a "mode"?
 */
export type ViewState = {
  inlineEditing: boolean;
  mode: ViewMode['mode'];
  /**
   * Per-source column overrides, keyed by source key. Absent entries fall back
   * to the source's `tableAttributes` (or the element-type defaults).
   */
  columns?: Record<string, SourceColumnState>;
  nestedInputNamespace?: string | null;
  showHeaderColumn: boolean;
  sort: Array<SortItem>;
  static: boolean;
};

export type SortOption = {
  label: string;
  attr: string;
  defaultDir: 'asc' | 'desc';
};
