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
import type {EditableTableCellType} from '@/types';
import CraftSwitch from '@craftcms/cp/vue/CraftSwitch.vue';

type MaybeGetter<T> = T | (() => T);

function resolve<T>(value: MaybeGetter<T>): T {
  return typeof value === 'function' ? (value as () => T)() : value;
}

interface InputColumnOptions<T extends Record<string, any>> {
  // TanStack column options
  header?: string | ((...args: any[]) => any);
  size?: number;
  meta?: Record<string, any>;

  // Shared cell options
  disabled?: MaybeGetter<boolean> | ((row: Row<T>) => boolean);

  // Text-specific options (singleline, email, url, number)
  class?: string;
  placeholder?: string;
  name?: (row: Row<T>, columnId: string) => string;

  // Switch-specific options (lightswitch)
  label?: string;
  ariaLabelledBy?: string;
  switchSize?: 'small' | 'medium';

  // Events
  onChange?: (
    value: any,
    ctx: Pick<CellContext<T, any>, 'row' | 'column'>
  ) => void;
  onInput?: (event: Event) => void;
  onUpdate?: (value: boolean | undefined) => void;
}

interface EditableColumnHelper<T extends Record<string, any>> {
  accessor: ColumnHelper<T>['accessor'];
  display: ColumnHelper<T>['display'];
  group: ColumnHelper<T>['group'];
  input: (
    accessor: Parameters<ColumnHelper<T>['accessor']>[0],
    type: EditableTableCellType,
    options?: InputColumnOptions<T>
  ) => ColumnDef<T, any>;
}

interface UseEditableTableOptions<T extends Record<string, any>> {
  data: () => T[] | Record<string, T>;
  columns: (options: {
    columnHelper: EditableColumnHelper<T>;
  }) => ColumnDef<T, any>[];
  key?: string;
  columnVisibility?: () => Record<string, boolean>;
  onChange: (data: T[] | Record<string, T>) => void;
}

const textInputTypes: Record<string, string> = {
  singleline: 'text',
  email: 'email',
  url: 'url',
  number: 'number',
};

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
    disabled: InputColumnOptions<T>['disabled'],
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
    cellOptions?: Pick<
      InputColumnOptions<T>,
      'class' | 'placeholder' | 'disabled' | 'name' | 'onChange' | 'onInput'
    >
  ): (ctx: CellContext<T, any>) => ReturnType<typeof h> {
    return ({row, column, getValue}) =>
      h('input', {
        type: inputType,
        value: getValue(),
        class: cellOptions?.class,
        placeholder: cellOptions?.placeholder,
        disabled: resolveDisabled(cellOptions?.disabled, row),
        name: cellOptions?.name?.(row, column.id),
        onInput: (event: Event) => {
          if (typeof cellOptions?.onInput === 'function') {
            cellOptions.onInput(event);
          }
        },
        onChange: (event: Event) => {
          if (typeof cellOptions?.onChange === 'function') {
            cellOptions.onChange(event, {row, column});
          }
          handleChange(
            row,
            column.id,
            (event.target as HTMLInputElement).value
          );
        },
      });
  }

  function switchCell(
    cellOptions?: Pick<
      InputColumnOptions<T>,
      'disabled' | 'label' | 'ariaLabelledBy' | 'switchSize' | 'onUpdate'
    >
  ): (ctx: CellContext<T, any>) => ReturnType<typeof h> {
    return ({row, column}) =>
      h(CraftSwitch, {
        modelValue: row.original[column.id],
        'label-sr-only': true,
        size: cellOptions?.switchSize ?? 'small',
        label: cellOptions?.label,
        'aria-labelledby': cellOptions?.ariaLabelledBy,
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
    cellOptions?: Pick<InputColumnOptions<T>, any>
  ): (ctx: CellContext<T, any>) => ReturnType<typeof h> {
    return ({row, column}) => {
      return h('input', {
        type: 'checkbox',
        checked: row.original[column.id],
        'aria-labelledby': cellOptions?.ariaLabelledBy,
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

  const columnHelper: EditableColumnHelper<T> = {
    accessor: baseHelper.accessor,
    display: baseHelper.display,
    group: baseHelper.group,

    input(accessor, type, inputOptions = {}) {
      const {
        header,
        size,
        meta,
        disabled,
        class: className,
        placeholder,
        name,
        label,
        ariaLabelledBy,
        switchSize,
        onChange,
        onInput,
        onUpdate,
      } = inputOptions;

      const columnDef: Record<string, any> = {};

      if (header !== undefined) columnDef.header = header;
      if (size !== undefined) columnDef.size = size;
      if (meta !== undefined) columnDef.meta = meta;

      const htmlType = textInputTypes[type];
      if (htmlType) {
        columnDef.cell = textInputCell(htmlType, {
          class: className,
          placeholder,
          disabled,
          name,
          onInput,
          onChange,
        });
      } else if (type === 'checkbox') {
        columnDef.cell = checkboxCell({
          disabled,
          label,
          ariaLabelledBy,
          switchSize,
          onChange,
        });
      } else if (type === 'lightswitch') {
        columnDef.cell = switchCell({
          disabled,
          label,
          ariaLabelledBy,
          switchSize,
          onUpdate,
        });
      } else {
        console.warn(
          `[useEditableTable] Column type "${type}" is not yet implemented. Rendering as plain text.`
        );
        columnDef.cell = ({getValue}: CellContext<T, any>) => getValue();
      }

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
