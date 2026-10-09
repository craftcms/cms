import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {computed, h, ref} from 'vue';
import {
  AdminTable,
  createCraftColumnHelper,
  useCraftTable,
  useServerPagination,
  useServerSort,
  type SortItem,
} from '@/admin-table';
import {DeleteButton, SearchForm} from '@/elements';
import {queryWidgets, widgets, type Widget} from './fixtures/widgets';

/**
 * Each story builds its table the way a plugin page does, importing only from
 * `@craftcms/cms/admin-table` and `@craftcms/cms/elements`. See the AdminTable
 * Guide for the walkthrough.
 */
const meta = {
  title: 'Elements/AdminTable Recipes',
  parameters: {
    docs: {
      description: {
        component:
          'AdminTable set up the way a plugin page uses it: column helpers, row actions, server-side pagination and sorting, and search.',
      },
    },
  },
} satisfies Meta;

export default meta;

type Story = StoryObj;

function widgetColumns(onDelete?: (widget: Widget) => void) {
  const columnHelper = createCraftColumnHelper<Widget>();

  return columnHelper.columns([
    columnHelper.link('name', {
      header: 'Name',
      props: ({row}) => ({href: `/admin/widgets/${row.original.id}`}),
    }),
    columnHelper.handle('handle'),
    columnHelper.accessor('type', {
      header: 'Type',
      meta: {headerTip: 'What kind of widget this is.'},
    }),
    columnHelper.date('dateCreated', {header: 'Date Created'}),
    ...(onDelete
      ? [
          columnHelper.actions(({row}) => [
            h(DeleteButton, {
              label: `Delete ${row.original.name}`,
              onClick: () => onDelete(row.original),
            }),
          ]),
        ]
      : []),
  ]);
}

/**
 * The column helper presets: `link` for the primary column, `handle` with
 * click-to-copy, `date`, and `actions` with a `DeleteButton` per row. The Type
 * header carries a `headerTip`.
 */
export const ColumnHelpers: Story = {
  render: () => ({
    components: {AdminTable},
    setup() {
      const data = ref(widgets.slice(0, 5));
      const columns = widgetColumns((widget) => {
        data.value = data.value.filter((w) => w.id !== widget.id);
      });

      const table = useCraftTable({
        get data() {
          return data.value;
        },
        columns,
        getRowId: (row) => String(row.id),
      });

      return {table};
    },
    template: '<AdminTable :table="table" title="Widgets" />',
  }),
};

/**
 * `useServerPagination` and `useServerSort` turn the table's page and sort
 * changes into query params. A plugin page sends them to its controller with
 * `router.visit()`; here they go to a local stand-in so the story can run.
 */
export const ServerPaginationAndSorting: Story = {
  render: () => ({
    components: {AdminTable},
    setup() {
      const query = ref({
        page: 1,
        perPage: 5,
        sort: [{field: 'name', direction: 'asc'}] as Array<SortItem>,
      });
      const response = computed(() => queryWidgets(query.value));

      const {paginationState, paginationConfig} = useServerPagination({
        initialState: response.value.pagination,
        currentQuery: () => ({}),
        onChange: ({query: next}) => {
          query.value = {
            ...query.value,
            page: Number(next.page),
            perPage: Number(next.per_page),
          };
          paginationState.value = {
            pageIndex: query.value.page - 1,
            pageSize: query.value.perPage,
          };
        },
      });

      const {sortingState, sortingConfig} = useServerSort({
        initialState: query.value.sort,
        currentQuery: () => ({}),
        onChange: ({query: next}) => {
          const sort = Object.values(
            next.sort as Record<number, SortItem>
          ).slice(0, 1);
          query.value = {...query.value, page: 1, sort};
          sortingState.value = sort.map((s) => ({
            id: s.field,
            desc: s.direction === 'desc',
          }));
          paginationState.value = {
            pageIndex: 0,
            pageSize: query.value.perPage,
          };
        },
      });

      const table = useCraftTable({
        get data() {
          return response.value.data;
        },
        columns: widgetColumns(),
        getRowId: (row) => String(row.id),
        state: {
          get pagination() {
            return paginationState.value;
          },
          get sorting() {
            return sortingState.value;
          },
        },
        ...paginationConfig,
        get rowCount() {
          return response.value.pagination.total;
        },
        ...sortingConfig,
        enableMultiSort: false,
      });

      return {table, response};
    },
    template: `
      <AdminTable
        :table="table"
        title="Widgets"
        :from="response.pagination.from"
        :to="response.pagination.to"
        :total="response.pagination.total"
        enable-adjust-page-size
        :page-size-options="[5, 10, 25]"
      />
    `,
  }),
};

/**
 * `SearchForm` in the `table-header` slot submits `search` to its `action`
 * after a pause in typing, reloading only the `data` and `searchTerm` props.
 */
export const WithSearch: Story = {
  render: () => ({
    components: {AdminTable, SearchForm},
    setup() {
      const searchTerm = ref('');
      const table = useCraftTable({
        get data() {
          const term = searchTerm.value.toLowerCase();
          return widgets
            .slice(0, 8)
            .filter((w) => w.name.toLowerCase().includes(term));
        },
        columns: widgetColumns(),
        getRowId: (row) => String(row.id),
      });

      return {table, searchTerm};
    },
    template: `
      <AdminTable :table="table" title="Widgets">
        <template #table-header>
          <SearchForm action="/admin/widgets" v-model="searchTerm" />
        </template>
      </AdminTable>
    `,
  }),
};
