import {t} from '@craftcms/ui';
import {h, type VNodeChild} from 'vue';
import type {InertiaLinkProps} from '@inertiajs/vue3';
import {
  type AccessorColumnDef,
  type CellContext,
  type ColumnDef,
  type ColumnHelper,
  createColumnHelper,
  type DisplayColumnDef,
} from '@tanstack/vue-table';
import type {CraftTableFeatures} from '@/modules/admin-table/tableFeatures';
import type {AccessorParam} from '@/modules/admin-table/composables/useEditableTable';
import CpLink from '@/common/components/CpLink.vue';
import Date from '@/common/components/Date.vue';
import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';

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
};

export function createCraftColumnHelper<T extends object>() {
  const baseHelper = createColumnHelper<CraftTableFeatures, T>();

  const columnHelper: CraftColumnHelper<T> = {
    accessor: baseHelper.accessor,
    columns: baseHelper.columns,
    display: baseHelper.display,
    group: baseHelper.group,

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
