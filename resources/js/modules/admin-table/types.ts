import type {ColumnDef} from '@tanstack/vue-table';
import type {CraftTableFeatures} from '@/common/table/craftTable';
import type {PaginatedRows} from '@/common/composables/usePaginatedRows';
import type {PaginationData} from '@/common/types';

export interface AdminTableRequest {
  page: number;
  perPage: number;
  search?: string;
  status?: string;
  sort?: Array<{field: string; direction: 'asc' | 'desc'}>;
}

export type AdminTablePage<TData> = PaginatedRows<TData>;

export interface AdminTableStatusOption {
  value: string;
  label: string;
  fill?: string | null;
}

export interface AdminTableReorder<TData> {
  row: TData;
  rows: TData[];
  startIndex: number;
  finishIndex: number;
  page: number;
  pageSize: number;
  paginated: boolean;
}

export interface AdminTableDataOptions<TData extends Record<string, any>> {
  rows: TData[];
  columns: ColumnDef<CraftTableFeatures, TData, any>[];
  loadRows?: (
    request: AdminTableRequest,
    signal: AbortSignal
  ) => Promise<AdminTablePage<TData>>;
  storageKey?: string;
  pagination?: PaginationData | null;
  requestState?: AdminTableRequest;
  pageSize: number;
  pageSizeOptions: number[];
  statusFilterOptions: AdminTableStatusOption[];
  columnsToggleable: boolean;
  hiddenColumnsByDefault: string[];
  reorderable: boolean;
  selectable: boolean;
  getSearchText?: (row: TData) => string;
  getRowStatus?: (row: TData) => string | undefined;
  getSortValue?: (row: TData, key: string) => unknown;
  canSelectRow?: (row: TData) => boolean;
}

export interface AdminTableHandle {
  selectedIds: Array<string | number>;
  refresh: () => Promise<boolean>;
  removeRows: (ids: Array<string | number>) => void;
  clearSelection: () => void;
}
