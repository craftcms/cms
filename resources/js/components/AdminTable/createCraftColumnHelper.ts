import {t} from '@craftcms/cp';
import {h} from 'vue';
import {type ColumnDef, createColumnHelper} from '@tanstack/vue-table';
import type {AccessorParam} from '@/composables/useEditableTable';
import CpLink from '@/components/CpLink.vue';

export interface CraftColumnHelper<
  T extends Record<string, any>,
> extends ReturnType<typeof createColumnHelper<T>> {
  handle: (accessor: AccessorParam<T>, config?: any) => ColumnDef<T, any>;
  link: (accessor: AccessorParam<T>, config?: any) => ColumnDef<T, any>;
  actions: (config?: any) => ColumnDef<T, any>;
}

export function createCraftColumnHelper<T extends Record<string, any>>() {
  const baseHelper = createColumnHelper<T>();

  const columnHelper: CraftColumnHelper<T> = {
    accessor: baseHelper.accessor,
    display: baseHelper.display,
    group: baseHelper.group,

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
        cell: (cellContext) =>
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
      });
    },

    handle(accessor, config = {}) {
      return baseHelper.accessor(accessor, {
        header: t('Handle'),
        cell: ({getValue}) =>
          h(
            'craft-copy-attribute',
            {
              value: getValue(),
            },
            getValue()
          ),
        ...config,
      });
    },
  };

  return columnHelper;
}
