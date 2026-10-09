import type {AdminTableStatusOption} from '@/modules/admin-table/types';
import type {UiNodePayload, UiValues} from './types';

export interface TableColumn {
  key: string;
  label: string;
  sortable?: boolean;
}

export interface TableLink {
  label: string;
  url?: string | null;
  /** Opens the URL's screen in a slideout, reloading the table once it's saved. */
  slideout?: boolean;
}

/** A menu item that opens a server-built form in a modal instead of following a link. */
export interface TableModalAction {
  label: string;
  modalUrl: string;
  actionUrl: string;
  params?: UiValues;
}

export interface TableMenu {
  label: string;
  items: Array<TableLink | TableModalAction>;
}

export interface TableIcon {
  icon: string;
  label?: string;
}

export interface TableHtml {
  html: string;
}

export interface BulkActionSingle {
  label: string;
  url: string;
  params?: Record<string, unknown>;
  allowMultiple?: boolean;
  fill?: string;
}

export interface BulkActionMenu {
  label?: string;
  icon?: string;
  items: BulkActionSingle[];
}

export type BulkActionDescriptor = BulkActionSingle | BulkActionMenu;

export type TableCellValue =
  | string
  | number
  | boolean
  | null
  | TableLink
  | TableLink[]
  | TableMenu
  | TableIcon
  | TableHtml;

export interface TableStatus {
  value?: string;
  fill: string;
  label: string | null;
}

export type TableRow = Record<string, TableCellValue> & {
  id?: string | number;
  _deletable?: boolean;
  _deleteUrl?: string;
  _deleteConfirmMessage?: string;
  _status?: TableStatus | null;
  _search?: string;
  _sort?: Record<string, string | number | boolean | null>;
};

export interface TableProps {
  columns: TableColumn[];
  rows: TableRow[];
  dataUrl: string | null;
  perPage: number;
  perPageOptions: number[];
  moveToPageUrl: string | null;
  emptyMessage: string | null;
  createLabel: string | null;
  createUrl: string | null;
  createMenuItems: Array<{label: string; url: string}> | null;
  createActionInPageHeader: boolean;
  reorderUrl: string | null;
  reorderSuccessMessage: string | null;
  reorderFailMessage: string | null;
  deleteUrl: string | null;
  deletable?: boolean;
  deleteConfirmMessage: string | null;
  deleteModalUrl: string | null;
  bulkDeleteUrl: string | null;
  bulkDeleteConfirmMessage: string | null;
  bulkActions: BulkActionDescriptor[];
  statusActions: BulkActionSingle[];
  statusFilterOptions: AdminTableStatusOption[];
  columnsToggleable: boolean;
  hiddenColumnsByDefault: string[];
  searchable: boolean;
  searchPlaceholder: string | null;
  bordered: boolean;
  showFooter?: boolean;
}

export type TableNodePayload = UiNodePayload<TableProps>;
