import type {RowData} from '@tanstack/vue-table';

declare module '@tanstack/vue-table' {
  interface ColumnMeta<TData extends RowData, TValue> {
    wrap?: boolean;
    // Applies classes to the cell
    cellClass?: string | Record<string, boolean>;
    cellTag?: 'td' | 'th';
    headerTip?: string;
    headerSrOnly?: boolean;
    // Applies classes to the header
    headerClass?: string | Record<string, boolean>;
    // Applies classes to both the header and cell at once
    columnClass?: string | Record<string, boolean>;
  }
}
