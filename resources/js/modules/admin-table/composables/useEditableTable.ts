import {
  computed,
  h,
  type HTMLAttributes,
  normalizeClass,
  shallowRef,
} from 'vue';
import {
  type CellContext,
  type ColumnDef,
  type ColumnHelper,
  createColumnHelper,
  getCoreRowModel,
  type Row,
  useVueTable,
} from '@tanstack/vue-table';
import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
import CraftCombobox from '@craftcms/ui/vue/CraftCombobox.vue';
import type {SelectItem} from '@/common/types';
import useCraftData from '@/common/composables/useCraftData';

type MaybeGetter<T> = T | (() => T);

function resolve<T>(value: MaybeGetter<T>): T {
  return value instanceof Function ? value() : value;
}

interface BaseColumnOptions<T extends object> {
  header?: ColumnDef<T>['header'];
  size?: number;
  class?: HTMLAttributes['class'];
  meta?: ColumnDef<T>['meta'];
  disabled?: MaybeGetter<boolean> | ((row: Row<T>) => boolean);
  placeholder?: string;
}

interface TextColumnOptions<T extends object> extends BaseColumnOptions<T> {
  inputType?: 'text' | 'email' | 'url' | 'number';
  placeholder?: string;
  name?: (row: Row<T>, columnId: string) => string;
  onChange?: (
    value: string,
    ctx: Pick<CellContext<T, unknown>, 'row' | 'column'>
  ) => void;
  onInput?: (event: Event) => void;
}

interface LightswitchColumnOptions<
  T extends object,
> extends BaseColumnOptions<T> {
  label?: string;
  ariaLabelledBy?: string;
  switchSize?: 'small' | 'medium';
  onUpdate?: (value: boolean | undefined) => void;
}

interface CheckboxColumnOptions<T extends object> extends BaseColumnOptions<T> {
  ariaLabelledBy?: string;
  onChange?: (
    value: boolean,
    ctx: Pick<CellContext<T, unknown>, 'row' | 'column'>
  ) => void;
}

interface AutocompleteColumnOptions<
  T extends object,
> extends BaseColumnOptions<T> {
  options?:
    | MaybeGetter<Array<SelectItem>>
    | ((row: Row<T>) => Array<SelectItem>);
  requireOptionMatch?: boolean;
  label?: string;
  onChange?: (
    value: string,
    ctx: Pick<CellContext<T, unknown>, 'row' | 'column'>
  ) => void;
}

export type AccessorParam<T extends object> = Parameters<
  ColumnHelper<T>['accessor']
>[0];

interface EditableColumnHelper<T extends object> {
  accessor: ColumnHelper<T>['accessor'];
  display: ColumnHelper<T>['display'];
  group: ColumnHelper<T>['group'];
  text: (
    accessor: AccessorParam<T>,
    options?: TextColumnOptions<T>
  ) => ColumnDef<T>;
  lightswitch: (
    accessor: AccessorParam<T>,
    options?: LightswitchColumnOptions<T>
  ) => ColumnDef<T>;
  checkbox: (
    accessor: AccessorParam<T>,
    options?: CheckboxColumnOptions<T>
  ) => ColumnDef<T>;
  autocomplete: (
    accessor: AccessorParam<T>,
    options?: AutocompleteColumnOptions<T>
  ) => ColumnDef<T>;
}

interface UseEditableTableOptions<T extends object> {
  data: () => T[] | Record<string, T>;
  columns: (options: {columnHelper: EditableColumnHelper<T>}) => ColumnDef<T>[];
  key?: string;
  name?: string;
  columnVisibility?: () => Record<string, boolean>;
  onChange: (data: T[] | Record<string, T>) => void;
}

export function useEditableTable<T extends object>(
  options: UseEditableTableOptions<T>
) {
  const key = options.key ?? 'id';
  const {readOnly} = useCraftData();

  function isRecord(data: T[] | Record<string, T>): data is Record<string, T> {
    return !Array.isArray(data);
  }

  const normalizedData = computed<T[]>(() => {
    const raw = options.data();

    if (isRecord(raw)) {
      // SAFETY: each normalized row is its original T value plus the configured record key.
      return Object.entries(raw).map(([k, value]) => ({
        ...value,
        [key]: k,
      })) as T[];
    }

    return raw;
  });

  function handleChange(
    row: Row<T>,
    columnId: string,
    value: string | boolean
  ): void {
    const raw = options.data();
    const wasRecord = isRecord(raw);

    if (wasRecord) {
      const record: Record<string, T> = {};
      Object.entries(raw).forEach(([recordKey, item], index) => {
        record[recordKey] =
          index === row.index ? {...item, [columnId]: value} : item;
      });
      options.onChange(record);
      return;
    }

    const updated = normalizedData.value.map((item, index) => {
      if (index === row.index) {
        return {...item, [columnId]: value};
      }
      return item;
    });

    options.onChange(updated);
  }

  function resolveDisabled<T extends object>(
    disabled: BaseColumnOptions<T>['disabled'],
    row: Row<T>
  ): boolean | undefined {
    let value = disabled;
    if (disabled instanceof Function) {
      value = disabled(row);
    }

    return readOnly ? true : Boolean(value);
  }

  function textInputCell(
    inputType: string,
    cellOptions?: Omit<
      TextColumnOptions<T>,
      'header' | 'size' | 'meta' | 'inputType'
    >
  ): (ctx: CellContext<T, unknown>) => ReturnType<typeof h> {
    return ({row, column}) =>
      h('textarea', {
        rows: 1,
        type: inputType,
        value: Object.getOwnPropertyDescriptor(row.original, column.id)?.value,
        class: normalizeClass([
          'cp-table-input cp-table-input--text',
          cellOptions?.class,
        ]),
        autocomplete: 'off',
        autocorrect: 'off',
        autocapitalize: 'off',
        spellcheck: false,
        placeholder: cellOptions?.placeholder,
        disabled: resolveDisabled(cellOptions?.disabled, row),
        name: cellOptions?.name
          ? cellOptions.name(row, column.id)
          : options.name
            ? `${options.name}[${String(Object.getOwnPropertyDescriptor(row.original, key)?.value)}][${column.id}]`
            : undefined,
        'aria-labelledby': `header-${column.id}`,
        onInput: (event: Event) => {
          cellOptions?.onInput?.(event);
        },
        onChange: (event: Event) => {
          if (!(event.target instanceof HTMLInputElement)) {
            return;
          }
          const value = event.target.value;
          cellOptions?.onChange?.(value, {row, column});
          handleChange(row, column.id, value);
        },
      });
  }

  function switchCell(
    cellOptions?: Omit<LightswitchColumnOptions<T>, 'header' | 'size' | 'meta'>
  ): (ctx: CellContext<T, unknown>) => ReturnType<typeof h> {
    return ({row, column}) =>
      h(CraftSwitch, {
        modelValue: Boolean(
          Object.getOwnPropertyDescriptor(row.original, column.id)?.value
        ),
        'label-sr-only': true,
        size: cellOptions?.switchSize ?? 'small',
        label: cellOptions?.label,
        class: normalizeClass([
          'cp-table-input cp-table-input--switch',
          cellOptions?.class,
        ]),
        'aria-labelledby': cellOptions?.ariaLabelledBy ?? `header-${column.id}`,
        disabled: resolveDisabled(cellOptions?.disabled, row),
        'onUpdate:modelValue': (value: boolean | undefined) => {
          cellOptions?.onUpdate?.(value);
          handleChange(row, column.id, value ?? false);
        },
      });
  }

  function checkboxCell(
    cellOptions?: Omit<CheckboxColumnOptions<T>, 'header' | 'size' | 'meta'>
  ): (ctx: CellContext<T, unknown>) => ReturnType<typeof h> {
    return ({row, column}) => {
      return h('input', {
        type: 'checkbox',
        checked: Boolean(
          Object.getOwnPropertyDescriptor(row.original, column.id)?.value
        ),
        class: normalizeClass([
          'cp-table-input cp-table-input--switch',
          cellOptions?.class,
        ]),
        'aria-labelledby': cellOptions?.ariaLabelledBy ?? `header-${column.id}`,
        disabled: resolveDisabled(cellOptions?.disabled, row),
        onChange: (event: Event) => {
          if (!(event.target instanceof HTMLInputElement)) {
            return;
          }
          const value = event.target.checked;
          cellOptions?.onChange?.(value, {row, column});
          handleChange(row, column.id, value ?? false);
        },
      });
    };
  }

  function autocompleteCell(
    cellOptions?: AutocompleteColumnOptions<T>
  ): (ctx: CellContext<T, unknown>) => ReturnType<typeof h> {
    return ({row, column}) => {
      const opts =
        cellOptions?.options instanceof Function
          ? cellOptions.options(row)
          : resolve(cellOptions?.options ?? []);

      return h(CraftCombobox, {
        modelValue: Object.getOwnPropertyDescriptor(row.original, column.id)
          ?.value,
        options: opts,
        class: normalizeClass([
          'cp-table-input cp-table-input--autocomplete',
          cellOptions?.class,
        ]),
        placeholder: cellOptions?.placeholder,
        label: cellOptions?.label ?? column.id,
        'label-sr-only': '',
        ...(cellOptions?.requireOptionMatch !== undefined && {
          requireOptionMatch: cellOptions.requireOptionMatch,
        }),
        disabled: resolveDisabled(cellOptions?.disabled, row),
        'onUpdate:modelValue': (
          value: string | number | boolean | undefined
        ) => {
          const strValue = String(value ?? '');
          cellOptions?.onChange?.(strValue, {row, column});
          handleChange(row, column.id, strValue);
        },
      });
    };
  }

  const baseHelper = createColumnHelper<T>();

  function buildColumnDef(
    accessor: AccessorParam<T>,
    base: BaseColumnOptions<T> | undefined
  ) {
    const columnDef: Parameters<ColumnHelper<T>['accessor']>[1] = {
      id: String(accessor),
    };
    if (base?.header !== undefined) columnDef.header = base.header;
    if (base?.size !== undefined) columnDef.size = base.size;
    if (base?.meta !== undefined) columnDef.meta = base.meta;
    return columnDef;
  }

  const columnHelper: EditableColumnHelper<T> = {
    accessor: baseHelper.accessor,
    display: baseHelper.display,
    group: baseHelper.group,

    text(accessor, opts = {}) {
      const {
        inputType,
        class: className,
        placeholder,
        name,
        onInput,
        onChange,
        ...base
      } = opts;
      const columnDef = buildColumnDef(accessor, base);
      columnDef.cell = textInputCell(inputType ?? 'text', {
        class: className,
        placeholder,
        disabled: base.disabled,
        name,
        onInput,
        onChange,
      });
      return baseHelper.accessor(accessor, columnDef);
    },

    lightswitch(accessor, opts = {}) {
      const {label, ariaLabelledBy, switchSize, onUpdate, ...base} = opts;
      const columnDef = buildColumnDef(accessor, base);
      columnDef.cell = switchCell({
        disabled: base.disabled,
        label,
        ariaLabelledBy,
        switchSize,
        onUpdate,
      });
      return baseHelper.accessor(accessor, columnDef);
    },

    checkbox(accessor, opts = {}) {
      const {ariaLabelledBy, onChange, ...base} = opts;
      const columnDef = buildColumnDef(accessor, base);
      columnDef.cell = checkboxCell({
        disabled: base.disabled,
        ariaLabelledBy,
        onChange,
      });
      return baseHelper.accessor(accessor, columnDef);
    },

    autocomplete(accessor, opts = {}) {
      const {options, requireOptionMatch, onChange, ...base} = opts;
      const columnDef = buildColumnDef(accessor, base);
      columnDef.cell = autocompleteCell({
        disabled: base.disabled,
        options,
        requireOptionMatch,
        onChange,
        class: opts.class ?? '',
        placeholder: opts.placeholder ?? '',
      });
      return baseHelper.accessor(accessor, columnDef);
    },
  };

  const columns = shallowRef(options.columns({columnHelper}));

  const tableOptions: Parameters<typeof useVueTable<T>>[0] = {
    get data() {
      return normalizedData.value;
    },
    get columns() {
      return columns.value;
    },
    enableSorting: false,
    getCoreRowModel: getCoreRowModel<T>(),
  };

  Object.assign(tableOptions, {defaultColumn: {size: 'auto'}});

  if (options.columnVisibility) {
    const columnVisibility = options.columnVisibility;
    tableOptions.state = {
      get columnVisibility() {
        return columnVisibility();
      },
    };
  }

  const table = useVueTable(tableOptions);

  return {table};
}
