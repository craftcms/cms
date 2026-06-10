import type {SortItem} from '@/common/types';

export type ViewMode = {
  mode: 'table' | 'cards' | 'structure';
  title: string;
  icon: string;
  structuresOnly?: boolean;
  availableOnMobile?: boolean;
};

/**
 * @TODO should inlineEditing and static be a "mode"?
 */
export type ViewState = {
  inlineEditing: boolean;
  mode: ViewMode['mode'];
  tableColumns: Array<string>;
  columnOrder?: Array<string>;
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
