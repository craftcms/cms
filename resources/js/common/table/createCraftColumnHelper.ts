import {t} from '@craftcms/ui';
import {h, type Component, type VNodeChild} from 'vue';
import type {InertiaLinkProps} from '@inertiajs/vue3';
import {
  type AccessorColumnDef,
  type CellContext,
  type ColumnDef,
  type ColumnHelper,
  createColumnHelper,
  type DisplayColumnDef,
} from '@tanstack/vue-table';
import type {CraftTableFeatures} from '@/common/table/craftTable';
import CheckboxCell from '@/common/table/cells/CheckboxCell.vue';
import SelectCell from '@/common/table/cells/SelectCell.vue';
import SelectRichCell from '@/common/table/cells/SelectRichCell.vue';
import type {SelectCellOption} from '@/common/table/cells/types';
import CpLink from '@/common/components/CpLink.vue';
import Date from '@/common/components/Date.vue';
import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';

export type AccessorParam<T extends object> = Parameters<
  ColumnHelper<CraftTableFeatures, T>['accessor']
>[0];

/**
 * A column whose cells are form controls. The value is read and written through
 * callbacks rather than a row property, so it can live anywhere.
 */
type ControlColumnDef<T extends object, V> = Omit<
  DisplayColumnDef<CraftTableFeatures, T>,
  'id' | 'cell'
> & {
  id: string;
  value: (row: T) => V;
  /** Names the control for screen readers, since the header can't. */
  label: (row: T) => string;
  onChange: (row: T, value: V) => void;
  disabled?: (row: T) => boolean;
  /** Leaves the cell empty for rows the control doesn't apply to. */
  hidden?: (row: T) => boolean;
};

type SelectColumnDef<T extends object> = ControlColumnDef<T, string> & {
  options: SelectCellOption[] | ((row: T) => SelectCellOption[]);
};

type ComponentProperties = Record<
  string,
  string | number | boolean | null | undefined
>;

type LinkColumnDef<T extends object> = AccessorColumnDef<
  CraftTableFeatures,
  T
> & {
  props: (
    cellContext: CellContext<CraftTableFeatures, T, unknown>
  ) => ComponentProperties & {href: InertiaLinkProps['href']};
};

type HtmlColumnDef<T extends object> = AccessorColumnDef<
  CraftTableFeatures,
  T
> & {
  props: (
    cellContext: CellContext<CraftTableFeatures, T, unknown>
  ) => ComponentProperties;
};

type DateColumnDef<T extends object> = {
  format?: string;
  header?: ColumnDef<CraftTableFeatures, T>['header'];
  size?: number;
  meta?: ColumnDef<CraftTableFeatures, T>['meta'];
};

export type CraftColumnHelper<T extends object> = ColumnHelper<
  CraftTableFeatures,
  T
> & {
  handle: (
    accessor: AccessorParam<T>,
    config?: Partial<AccessorColumnDef<CraftTableFeatures, T>>
  ) => AccessorColumnDef<CraftTableFeatures, T, unknown>;
  html: (
    accessor: AccessorParam<T>,
    config?: Partial<HtmlColumnDef<T>>
  ) => AccessorColumnDef<CraftTableFeatures, T, unknown>;
  link: (
    accessor: AccessorParam<T>,
    config?: Partial<LinkColumnDef<T>>
  ) => AccessorColumnDef<CraftTableFeatures, T, unknown>;
  actions: (
    actions: (
      cellContext: CellContext<CraftTableFeatures, T, unknown>
    ) => VNodeChild[],
    config?: Partial<DisplayColumnDef<CraftTableFeatures, T>>
  ) => ColumnDef<CraftTableFeatures, T, unknown>;
  date: (
    accessor: AccessorParam<T>,
    config?: Partial<DateColumnDef<T>>
  ) => AccessorColumnDef<CraftTableFeatures, T, unknown>;
  /** A native select: the platform picker, plain text options. */
  select: (
    config: SelectColumnDef<T>
  ) => DisplayColumnDef<CraftTableFeatures, T, unknown>;
  /** A rich select, whose options can carry a hint beside their label. */
  selectRich: (
    config: SelectColumnDef<T>
  ) => DisplayColumnDef<CraftTableFeatures, T, unknown>;
  checkbox: (
    config: ControlColumnDef<T, boolean>
  ) => DisplayColumnDef<CraftTableFeatures, T, unknown>;
};

export function createCraftColumnHelper<T extends object>() {
  const baseHelper = createColumnHelper<CraftTableFeatures, T>();

  function selectColumn(component: Component, config: SelectColumnDef<T>) {
    const {value, label, onChange, disabled, hidden, options, ...rest} = config;

    return baseHelper.display({
      ...rest,
      cell: ({row}) =>
        hidden?.(row.original)
          ? null
          : h(component, {
              value: value(row.original),
              options:
                typeof options === 'function' ? options(row.original) : options,
              label: label(row.original),
              disabled: disabled?.(row.original) ?? false,
              onChange: (next: string) => onChange(row.original, next),
            }),
    });
  }

  const columnHelper: CraftColumnHelper<T> = {
    accessor: baseHelper.accessor,
    columns: baseHelper.columns,
    display: baseHelper.display,
    group: baseHelper.group,

    select: (config) => selectColumn(SelectCell, config),

    selectRich: (config) => selectColumn(SelectRichCell, config),

    checkbox(config) {
      const {value, label, onChange, disabled, hidden, ...rest} = config;

      return baseHelper.display({
        ...rest,
        cell: ({row}) =>
          hidden?.(row.original)
            ? null
            : h(CheckboxCell, {
                checked: value(row.original),
                label: label(row.original),
                disabled: disabled?.(row.original) ?? false,
                onChange: (next: boolean) => onChange(row.original, next),
              }),
      });
    },

    date(accessor, config = {}) {
      // oxlint-disable-next-line @typescript-eslint/no-unused-vars
      const {format, ...rest} = config;
      const columnDef: Parameters<
        ColumnHelper<CraftTableFeatures, T>['accessor']
      >[1] = {
        id: String(accessor),
        cell: (cellContext: CellContext<CraftTableFeatures, T, unknown>) => {
          const value = cellContext.getValue();
          if (Object(value).constructor === String) {
            return h(Date, {value: String(value)});
          }
          if (
            value instanceof Object &&
            'date' in value &&
            Object(value.date).constructor === String
          ) {
            return h(Date, {value: String(value.date)});
          }

          throw new Error('Date columns require a string or dated value.');
        },
        ...rest,
      };
      return baseHelper.accessor(accessor, columnDef);
    },

    actions(actions = () => [], config = {}) {
      return baseHelper.display({
        id: 'actions',
        header: t('Actions'),
        meta: {
          headerSrOnly: true,
          ...config.meta,
        },
        cell: (cellContext) =>
          h(
            'div',
            {
              class: 'flex gap-2 items-center justify-end self-end',
            },
            actions(cellContext)
          ),
      });
    },

    link(accessor, config = {}) {
      const {props, ...rest} = config;

      const columnDef: Parameters<
        ColumnHelper<CraftTableFeatures, T>['accessor']
      >[1] = {
        id: String(accessor),
        // With nothing to link to, the value is shown as it is.
        cell: (cellContext: CellContext<CraftTableFeatures, T, any>) =>
          h('div', [
            props
              ? h(
                  CpLink,
                  {
                    class: 'font-bold',
                    ...props(cellContext),
                  },
                  () => cellContext.getValue()
                )
              : cellContext.getValue(),
          ]),
        ...rest,
      };
      return baseHelper.accessor(accessor, columnDef);
    },

    handle(accessor, config = {}) {
      const columnDef: Parameters<
        ColumnHelper<CraftTableFeatures, T>['accessor']
      >[1] = {
        id: String(accessor),
        header: t('Handle'),
        cell: ({getValue}: CellContext<CraftTableFeatures, T, any>) =>
          h(
            'craft-copy-attribute',
            {
              value: getValue(),
            },
            String(getValue())
          ),
        ...config,
      };
      return baseHelper.accessor(accessor, columnDef);
    },

    html(accessor, config = {}) {
      const {props = () => ({}), ...rest} = config;

      const columnDef: Parameters<
        ColumnHelper<CraftTableFeatures, T>['accessor']
      >[1] = {
        id: String(accessor),
        cell: (cellContext: CellContext<CraftTableFeatures, T, any>) =>
          h(DynamicHtmlRenderer, {
            html: cellContext.getValue(),
            ...props(cellContext),
          }),
        ...rest,
      };
      return baseHelper.accessor(accessor, columnDef);
    },
  };

  return columnHelper;
}
