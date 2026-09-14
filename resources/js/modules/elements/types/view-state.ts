import type {SortItem} from '@/common/types';

export type ViewMode = {
  mode: 'table' | 'cards' | 'structure' | 'thumbs';
  title: string;
  icon: string;
  structuresOnly?: boolean;
  availableOnMobile?: boolean;
};

/** A source's persisted view overrides (columns + sort). */
export type SourceViewState = {
  /**
   * Columns the user has explicitly chosen to show, in display order — the
   * single source of column-order truth. Checking a column appends it;
   * dragging in the View popover rewrites the order. Unchecked columns have
   * no persisted order (they present alphabetically).
   */
  visible?: Array<string>;
  /**
   * The user's explicit sort for this source. Absent entries fall back to the
   * source's `defaultSort` (resolved server-side).
   */
  sort?: Array<SortItem>;
};

/**
 * @TODO should inlineEditing and static be a "mode"?
 */
export type ViewState = {
  inlineEditing: boolean;
  mode: ViewMode['mode'];
  /**
   * Per-source view overrides (columns + sort), keyed by source key. Absent
   * entries fall back to the source's own config (`tableAttributes` /
   * `defaultSort`) or the element-type defaults.
   */
  sources?: Record<string, SourceViewState>;
  nestedInputNamespace?: string | null;
  showHeaderColumn: boolean;
  static: boolean;
};

/** A sortable attribute, as serialized by `ElementIndexes::sortOptions()`. */
export type SortOption = {
  label: string;
  value: string;
  defaultDir: 'asc' | 'desc';
};
