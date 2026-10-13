import type {EditableTableColumns} from '../../editable-table/types';
import type {UiValues, NestedUiPayload} from '../types';

export type TableValue = UiValues[] | Record<string, UiValues> | null;
export type TableRow = {
  id: string;
  key: string;
  value: UiValues;
  ui?: NestedUiPayload;
};
export type TableControlProps = {
  columns: EditableTableColumns;
  rowTemplate?: NestedUiPayload;
  allowAdd?: boolean;
  allowDelete?: boolean;
  allowReorder?: boolean;
  minRows?: number;
  maxRows?: number;
  keyed?: boolean;
  rowIdPrefix?: string;
  hiddenRows?: string[];
  defaultValues?: UiValues;
  addRowLabel?: string;
  includeRowId?: boolean | string;
  staticRows?: boolean;
  errors?: Record<string, Record<string, true>>;
};
