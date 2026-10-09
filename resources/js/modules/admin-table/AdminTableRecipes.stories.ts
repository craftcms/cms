import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {h, ref} from 'vue';
import {
  AdminTable,
  createCraftColumnHelper,
  DeleteButton,
  SearchForm,
  useCraftTable,
} from '@/cp-module';
import {useWidgetPageProps, widgets, type Widget} from './fixtures/widgets';

/**
 * Each story builds its table the way a plugin page does, importing only from
 * `@craftcms/cp`. See the AdminTable Guide for the walkthrough.
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
 * The `inertia` option pages and sorts the table on the server: each change
 * visits the page's own URL and reloads the rows, `pagination` and `sort`
 * props. Here a stand-in controller answers the visits so the story can run.
 */
export const ServerPaginationAndSorting: Story = {
  render: () => ({
    components: {AdminTable},
    setup() {
      const {props} = useWidgetPageProps();

      const table = useCraftTable({
        get data() {
          return props.widgets;
        },
        columns: widgetColumns(),
        getRowId: (row) => String(row.id),
        inertia: {
          url: '/admin/widgets',
          pagination: () => props.pagination,
          sort: () => props.sort,
          dataProp: 'widgets',
        },
      });

      return {table, props};
    },
    template: `
      <AdminTable
        :table="table"
        title="Widgets"
        :from="props.pagination.from"
        :to="props.pagination.to"
        :total="props.pagination.total"
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
