import type {GarnishBaseSettings} from '@craftcms/garnish';
import type {EditableTableCellType} from '@/common/types';
import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';

/**
 * A column definition for the editable table. Mirrors the `col` objects emitted
 * by `_includes/forms/editableTable.twig` / `Craft\Cp\FormFields::editableTable`.
 * Kept loose (`[key: string]: any`) because the PHP side is highly dynamic.
 */
export interface EditableTableColumn {
    type: EditableTableCellType | string;
    class?: string;
    heading?: string;
    width?: string | number;
    placeholder?: string;
    rows?: number;
    code?: boolean;
    value?: string | number;
    options?: Record<string, any> | any[];
    textExpanderTriggers?: TextExpanderTriggers;
    /** Checkbox: only one in the column may be checked at a time. */
    radioMode?: boolean;
    /** Checkbox: column IDs to show/hide based on the checkbox state. */
    toggle?: string[];
    /** Auto-populate this column's value (a handle) from another column. */
    autopopulate?: string;
    /** Number column: locale used for formatting/parsing. */
    locale?: string;
    [key: string]: any;
}

/** Map of column ID → column definition. */
export type EditableTableColumns = Record<string, EditableTableColumn>;

/**
 * Settings accepted by {@link EditableTable}. Matches the legacy
 * `Craft.EditableTable.defaults` and the JSON emitted by the editable-table
 * Twig include's `{% js %}` block.
 */
export interface EditableTableSettings extends GarnishBaseSettings {
    /** Prefix prepended to generated numeric row IDs (e.g. `row` → `row0`). */
    rowIdPrefix: string;
    /** Default `{colId: value}` applied to newly added rows. */
    defaultValues: Record<string, any>;
    allowAdd: boolean;
    allowReorder: boolean;
    allowDelete: boolean;
    minRows: number | null;
    maxRows: number | null;
    /** Defer Row object creation until a row is first interacted with. */
    lazyInitRows: boolean;
    /** Callback invoked with the new row's jQuery `<tr>` after it's added. */
    onAddRow: (...args: any[]) => void;
    /** Callback invoked with the deleted row's jQuery `<tr>`. */
    onDeleteRow: (...args: any[]) => void;
    /** Disable all row mutation (display-only). */
    staticRows: boolean;
    /** Include a hidden `rowId` input per row. */
    includeRowId: boolean;
    maxRowId: number | null;
}
