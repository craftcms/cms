import {t} from '@craftcms/cp';
import {h} from 'vue';
import {
  type AccessorColumnDef,
  type CellContext,
  type ColumnDef,
  type ColumnHelper,
  createColumnHelper,
  type DisplayColumnDef,
} from '@tanstack/vue-table';
import type {AccessorParam} from '@/composables/useEditableTable';
import CpLink from '@/components/CpLink.vue';
import Date from '@/components/Date.vue';

type LinkColumnDef<T extends Record<string, any>> = DisplayColumnDef<T> & {
  props: (cellContext: CellContext<T, any>) => Record<string, any>;
};

type DateColumnDef<T extends Record<string, any>> = DisplayColumnDef<T> & {
  format?: string;
};

export type CraftColumnHelper<T extends Record<string, any>> =
  ColumnHelper<T> & {
    handle: (
      accessor: AccessorParam<T>,
      config?: Partial<DisplayColumnDef<T>>
    ) => AccessorColumnDef<T, any>;
    link: (
      accessor: AccessorParam<T>,
      config?: Partial<LinkColumnDef<T>>
    ) => AccessorColumnDef<T, any>;
    actions: (
      actions: (cellContext: CellContext<T, any>) => Array<any>,
      config?: Partial<DisplayColumnDef<T>>
    ) => ColumnDef<T, any>;
    date: (
      accessor: AccessorParam<T>,
      config?: Partial<DateColumnDef<T>>
    ) => AccessorColumnDef<T, any>;
  };

export function createCraftColumnHelper<T extends Record<string, any>>() {
  const baseHelper = createColumnHelper<T>();

  const columnHelper: CraftColumnHelper<T> = {
    accessor: baseHelper.accessor,
    display: baseHelper.display,
    group: baseHelper.group,

    date(accessor, config = {}) {
      const {format, ...rest} = config;
      return baseHelper.accessor(accessor, {
        cell: (cellContext: CellContext<T, any>) => {
          if (cellContext.getValue()) {
            if (typeof cellContext.getValue() === 'string') {
              return h(Date, {value: cellContext.getValue()});
            } else if (
              typeof cellContext.getValue() === 'object' &&
              Object.keys(cellContext.getValue()).includes('date')
            ) {
              return h(Date, {value: cellContext.getValue().date});
            }
          }

          return 'Never';
        },
        ...rest,
      } as any);
    },

    actions(actions = () => [], config = {}) {
      return baseHelper.display({
        id: 'actions',
        header: t('Actions'),
        meta: {
          headerSrOnly: true,
          ...(config.meta || {}),
        },
        cell: (cellContext) =>
          h(
            'div',
            {
              class: 'flex gap-2 items-center justify-end self-end',
            },
            actions(cellContext)
          ),
        ...config,
      });
    },

    link(accessor, config = {}) {
      const {props = () => ({}), ...rest} = config;

      return baseHelper.accessor(accessor, {
        cell: (cellContext: CellContext<T, any>) =>
          h('div', [
            h(
              CpLink,
              {
                class: 'font-bold',
                inertia: false,
                ...props(cellContext),
              },
              () => cellContext.getValue()
            ),
          ]),
        ...rest,
      } as any);
    },

    handle(accessor, config = {}) {
      return baseHelper.accessor(accessor, {
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
      } as any);
    },
  };

  return columnHelper;
}
