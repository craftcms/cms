import {t} from '@craftcms/ui';
import {h, type VNodeChild} from 'vue';
import {
  type AccessorColumnDef,
  type CellContext,
  type ColumnDef,
  type ColumnHelper,
  createColumnHelper,
  type DisplayColumnDef,
} from '@tanstack/vue-table';
import type {AccessorParam} from '@/modules/admin-table/composables/useEditableTable';
import CpLink from '@/common/components/CpLink.vue';
import Date from '@/common/components/Date.vue';
import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';

type ComponentProperties = Record<
  string,
  string | number | boolean | null | undefined
>;

type LinkColumnDef<T extends object> = AccessorColumnDef<T> & {
  props: (cellContext: CellContext<T, unknown>) => ComponentProperties;
};

type HtmlColumnDef<T extends object> = AccessorColumnDef<T> & {
  props: (cellContext: CellContext<T, unknown>) => ComponentProperties;
};

type DateColumnDef<T extends object> = {
  format?: string;
  header?: ColumnDef<T>['header'];
  size?: number;
  meta?: ColumnDef<T>['meta'];
};

export type CraftColumnHelper<T extends object> = ColumnHelper<T> & {
  handle: (
    accessor: AccessorParam<T>,
    config?: Partial<AccessorColumnDef<T>>
  ) => AccessorColumnDef<T, unknown>;
  html: (
    accessor: AccessorParam<T>,
    config?: Partial<HtmlColumnDef<T>>
  ) => AccessorColumnDef<T, unknown>;
  link: (
    accessor: AccessorParam<T>,
    config?: Partial<LinkColumnDef<T>>
  ) => AccessorColumnDef<T, unknown>;
  actions: (
    actions: (cellContext: CellContext<T, unknown>) => VNodeChild[],
    config?: Partial<DisplayColumnDef<T>>
  ) => ColumnDef<T, unknown>;
  date: (
    accessor: AccessorParam<T>,
    config?: Partial<DateColumnDef<T>>
  ) => AccessorColumnDef<T, unknown>;
};

export function createCraftColumnHelper<T extends object>() {
  const baseHelper = createColumnHelper<T>();

  const columnHelper: CraftColumnHelper<T> = {
    accessor: baseHelper.accessor,
    display: baseHelper.display,
    group: baseHelper.group,

    date(accessor, config = {}) {
      // oxlint-disable-next-line @typescript-eslint/no-unused-vars
      const {format, ...rest} = config;
      const columnDef: Parameters<ColumnHelper<T>['accessor']>[1] = {
        id: String(accessor),
        cell: (cellContext: CellContext<T, unknown>) => {
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
      const {props = () => ({}), ...rest} = config;

      const columnDef: Parameters<ColumnHelper<T>['accessor']>[1] = {
        id: String(accessor),
        cell: (cellContext: CellContext<T, any>) =>
          h('div', [
            h(
              CpLink,
              {
                class: 'font-bold',
                ...props(cellContext),
              },
              () => cellContext.getValue()
            ),
          ]),
        ...rest,
      };
      return baseHelper.accessor(accessor, columnDef);
    },

    handle(accessor, config = {}) {
      const columnDef: Parameters<ColumnHelper<T>['accessor']>[1] = {
        id: String(accessor),
        header: t('Handle'),
        cell: ({getValue}: CellContext<T, any>) =>
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

      const columnDef: Parameters<ColumnHelper<T>['accessor']>[1] = {
        id: String(accessor),
        cell: (cellContext: CellContext<T, any>) =>
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
