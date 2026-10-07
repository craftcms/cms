import type {EditableTableColumns} from '../../editable-table/types';
import type {FormValues, NestedFormPayload} from '../types';

export type TableValue = FormValues[] | Record<string, FormValues> | null;
export type TableRow = {
  id: string;
  key: string;
  value: FormValues;
  form?: NestedFormPayload;
};
export type TableControlProps = {
  columns: EditableTableColumns;
  rowTemplate?: NestedFormPayload;
  allowAdd?: boolean;
  allowDelete?: boolean;
  allowReorder?: boolean;
  minRows?: number;
  maxRows?: number;
  keyed?: boolean;
  rowIdPrefix?: string;
  defaultValues?: FormValues;
  addRowLabel?: string;
  includeRowId?: boolean | string;
  staticRows?: boolean;
  errors?: Record<string, Record<string, true>>;
};
