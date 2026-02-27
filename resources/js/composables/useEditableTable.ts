import {computed, h, ref, type Ref} from 'vue';
import {
  type CellContext,
  type ColumnDef,
  type ColumnHelper,
  createColumnHelper,
  getCoreRowModel,
  type Row,
  useVueTable,
} from '@tanstack/vue-table';
import CraftSwitch from '@craftcms/cp/vue/CraftSwitch.vue';

type MaybeGetter<T> = T | (() => T);

function resolve<T>(value: MaybeGetter<T>): T {
  return typeof value === 'function' ? (value as () => T)() : value;
}

interface BaseColumnOptions<T extends Record<string, any>> {
  header?: string | ((...args: any[]) => any);
  size?: number;
  meta?: Record<string, any>;
  disabled?: MaybeGetter<boolean> | ((row: Row<T>) => boolean);
}

interface TextColumnOptions<
  T extends Record<string, any>,
> extends BaseColumnOptions<T> {
  inputType?: 'text' | 'email' | 'url' | 'number';
  class?: string;
  placeholder?: string;
  name?: (row: Row<T>, columnId: string) => string;
  onChange?: (
    value: any,
    ctx: Pick<CellContext<T, any>, 'row' | 'column'>
  ) => void;
  onInput?: (event: Event) => void;
}

interface LightswitchColumnOptions<
  T extends Record<string, any>,
> extends BaseColumnOptions<T> {
  label?: string;
  ariaLabelledBy?: string;
  switchSize?: 'small' | 'medium';
  onUpdate?: (value: boolean | undefined) => void;
}

interface CheckboxColumnOptions<
  T extends Record<string, any>,
> extends BaseColumnOptions<T> {
  ariaLabelledBy?: string;
  onChange?: (
    value: any,
    ctx: Pick<CellContext<T, any>, 'row' | 'column'>
  ) => void;
}

type AccessorParam<T extends Record<string, any>> = Parameters<
  ColumnHelper<T>['accessor']
>[0];

interface EditableColumnHelper<T extends Record<string, any>> {
  accessor: ColumnHelper<T>['accessor'];
  display: ColumnHelper<T>['display'];
  group: ColumnHelper<T>['group'];
  text: (
    accessor: AccessorParam<T>,
    options?: TextColumnOptions<T>
  ) => ColumnDef<T, any>;
  lightswitch: (
    accessor: AccessorParam<T>,
    options?: LightswitchColumnOptions<T>
  ) => ColumnDef<T, any>;
  checkbox: (
    accessor: AccessorParam<T>,
    options?: CheckboxColumnOptions<T>
  ) => ColumnDef<T, any>;
}

interface UseEditableTableOptions<T extends Record<string, any>> {
  data: () => T[] | Record<string, T>;
  columns: (options: {
    columnHelper: EditableColumnHelper<T>;
  }) => ColumnDef<T, any>[];
  key?: string;
  name?: string;
  columnVisibility?: () => Record<string, boolean>;
  onChange: (data: T[] | Record<string, T>) => void;
}

export function useEditableTable<T extends Record<string, any>>(
  options: UseEditableTableOptions<T>
) {
  const key = options.key ?? 'id';

  function isRecord(data: T[] | Record<string, T>): data is Record<string, T> {
    return !Array.isArray(data);
  }

  const normalizedData = computed<T[]>(() => {
    const raw = options.data();

    if (isRecord(raw)) {
      return Object.entries(raw).map(([k, value]) => ({
        ...value,
        [key]: k,
      })) as T[];
    }

    return raw;
  });

  function handleChange(row: Row<T>, columnId: string, value: any): void {
    const raw = options.data();
    const wasRecord = isRecord(raw);

    const updated = normalizedData.value.map((item, index) => {
      if (index === row.index) {
        return {...item, [columnId]: value};
      }
      return item;
    });

    if (wasRecord) {
      const record = {} as Record<string, T>;
      for (const item of updated) {
        const {[key]: k, ...rest} = item;
        record[k as string] = rest as T;
      }
      options.onChange(record);
    } else {
      options.onChange(updated);
    }
  }

  function resolveDisabled<T extends Record<string, any>>(
    disabled: BaseColumnOptions<T>['disabled'],
    row: Row<T>
  ): boolean | undefined {
    if (disabled === undefined) return undefined;
    if (typeof disabled === 'boolean') return disabled;
    if (typeof disabled === 'function') {
      // Check if it's a row-aware function (has parameters) or a simple getter
      return (disabled as (row: Row<T>) => boolean)(row);
    }
    return undefined;
  }

  function textInputCell(
    inputType: string,
    cellOptions?: Omit<
      TextColumnOptions<T>,
      'header' | 'size' | 'meta' | 'inputType'
    >
  ): (ctx: CellContext<T, any>) => ReturnType<typeof h> {
    return ({row, column, getValue}) =>
      h('input', {
        type: inputType,
        value: getValue(),
        class: cellOptions?.class,
        placeholder: cellOptions?.placeholder,
        disabled: resolveDisabled(cellOptions?.disabled, row),
        name: cellOptions?.name
          ? cellOptions.name(row, column.id)
          : options.name
            ? `${options.name}[${row.original[key]}][${column.id}]`
            : undefined,
        'aria-labelledby': `header-${column.id}`,
        onInput: (event: Event) => {
          if (typeof cellOptions?.onInput === 'function') {
            cellOptions.onInput(event);
          }
        },
        onChange: (event: Event) => {
          const value = (event.target as HTMLInputElement).value;
          if (typeof cellOptions?.onChange === 'function') {
            cellOptions.onChange(value, {row, column});
          }
          handleChange(row, column.id, value);
        },
      });
  }

  function switchCell(
    cellOptions?: Omit<LightswitchColumnOptions<T>, 'header' | 'size' | 'meta'>
  ): (ctx: CellContext<T, any>) => ReturnType<typeof h> {
    return ({row, column}) =>
      h(CraftSwitch, {
        modelValue: row.original[column.id],
        'label-sr-only': true,
        size: cellOptions?.switchSize ?? 'small',
        label: cellOptions?.label,
        'aria-labelledby': cellOptions?.ariaLabelledBy ?? `header-${column.id}`,
        disabled: resolveDisabled(cellOptions?.disabled, row),
        'onUpdate:modelValue': (value: boolean | undefined) => {
          if (typeof cellOptions?.onUpdate === 'function') {
            cellOptions.onUpdate(value);
          }
          handleChange(row, column.id, value ?? false);
        },
      });
  }

  function checkboxCell(
    cellOptions?: Omit<CheckboxColumnOptions<T>, 'header' | 'size' | 'meta'>
  ): (ctx: CellContext<T, any>) => ReturnType<typeof h> {
    return ({row, column}) => {
      return h('input', {
        type: 'checkbox',
        checked: row.original[column.id],
        'aria-labelledby': cellOptions?.ariaLabelledBy ?? `header-${column.id}`,
        disabled: resolveDisabled(cellOptions?.disabled, row),
        onChange: (event: Event) => {
          const value = (event.target as HTMLInputElement).checked;
          if (typeof cellOptions?.onChange === 'function') {
            cellOptions.onChange(value, {row, column});
          }
          handleChange(row, column.id, value ?? false);
        },
      });
    };
  }

  const baseHelper = createColumnHelper<T>();

  function buildColumnDef(
    base: BaseColumnOptions<T> | undefined
  ): Record<string, any> {
    const columnDef: Record<string, any> = {};
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
      const columnDef = buildColumnDef(base);
      columnDef.cell = textInputCell(inputType ?? 'text', {
        class: className,
        placeholder,
        disabled: base.disabled,
        name,
        onInput,
        onChange,
      });
      return baseHelper.accessor(accessor as any, columnDef);
    },

    lightswitch(accessor, opts = {}) {
      const {label, ariaLabelledBy, switchSize, onUpdate, ...base} = opts;
      const columnDef = buildColumnDef(base);
      columnDef.cell = switchCell({
        disabled: base.disabled,
        label,
        ariaLabelledBy,
        switchSize,
        onUpdate,
      });
      return baseHelper.accessor(accessor as any, columnDef);
    },

    checkbox(accessor, opts = {}) {
      const {ariaLabelledBy, onChange, ...base} = opts;
      const columnDef = buildColumnDef(base);
      columnDef.cell = checkboxCell({
        disabled: base.disabled,
        ariaLabelledBy,
        onChange,
      });
      return baseHelper.accessor(accessor as any, columnDef);
    },
  };

  const columns = ref(options.columns({columnHelper})) as Ref<
    ColumnDef<T, any>[]
  >;

  const tableOptions: Parameters<typeof useVueTable<T>>[0] = {
    get data() {
      return normalizedData.value;
    },
    get columns() {
      return columns.value;
    },
    enableSorting: false,
    getCoreRowModel: getCoreRowModel<T>(),
    defaultColumn: {
      size: 'auto' as unknown as number,
    },
  };

  if (options.columnVisibility) {
    tableOptions.state = {
      get columnVisibility() {
        return options.columnVisibility!();
      },
    };
  }

  const table = useVueTable(tableOptions);

  return {table};
}
