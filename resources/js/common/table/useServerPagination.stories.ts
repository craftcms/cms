import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {computed, defineComponent, onBeforeUnmount, ref} from 'vue';
import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
import {useCraftTable} from './craftTable';
import {createCraftColumnHelper} from './createCraftColumnHelper';
import {useServerPagination} from './useServerPagination';
import {
  queryWidgets,
  toQueryString,
  type Widget,
} from '@/modules/admin-table/fixtures/widgets';

const columnHelper = createCraftColumnHelper<Widget>();
const columns = columnHelper.columns([
  columnHelper.accessor('name', {header: 'Name'}),
  columnHelper.handle('handle'),
  columnHelper.accessor('type', {header: 'Type'}),
]);

/**
 * An AdminTable paged through `useServerPagination`, next to what the
 * composable hands `onChange`. A local stand-in plays the controller, and the
 * story applies the new state itself, as a page's remount would.
 */
const PaginatedTable = defineComponent({
  components: {AdminTable},
  props: {
    perPage: {type: Number, default: 5},
    pageTrigger: {type: String, default: 'page'},
    withCurrentQuery: {type: Boolean, default: false},
  },
  setup(props) {
    const originalTrigger = window.Craft.pageTrigger;
    window.Craft.pageTrigger = props.pageTrigger;
    onBeforeUnmount(() => (window.Craft.pageTrigger = originalTrigger));

    const query = ref({page: 1, perPage: props.perPage, sort: []});
    const response = computed(() => queryWidgets(query.value));
    const lastChange = ref<{
      query: Record<string, string | number>;
      state: unknown;
    } | null>(null);

    const {paginationState, paginationConfig} = useServerPagination({
      initialState: response.value.pagination,
      currentQuery: props.withCurrentQuery
        ? () => ({source: 'gadgets', search: 'blue'})
        : undefined,
      onChange: (change) => {
        lastChange.value = change;
        query.value = {
          ...query.value,
          page: Number(change.query[props.pageTrigger]),
          perPage: Number(change.query.per_page),
        };
        paginationState.value = {
          pageIndex: query.value.page - 1,
          pageSize: query.value.perPage,
        };
      },
    });

    const table = useCraftTable({
      get data() {
        return response.value.data;
      },
      columns,
      getRowId: (row) => String(row.id),
      state: {
        get pagination() {
          return paginationState.value;
        },
      },
      ...paginationConfig,
      get rowCount() {
        return response.value.pagination.total;
      },
    });

    const queryString = computed(() =>
      lastChange.value ? toQueryString(lastChange.value.query) : ''
    );

    return {table, response, lastChange, queryString, paginationState};
  },
  template: `
    <div class="flex flex-col gap-4">
      <AdminTable
        :table="table"
        title="Widgets"
        :from="response.pagination.from"
        :to="response.pagination.to"
        :total="response.pagination.total"
        enable-adjust-page-size
        :page-size-options="[5, 10, 25]"
      />
      <div class="text-xs flex flex-col gap-2">
        <p v-if="!lastChange">Change the page or page size to see what <code>onChange</code> receives.</p>
        <template v-else>
          <div><strong>onChange query</strong><pre>{{ lastChange.query }}</pre></div>
          <div><strong>As a URL</strong><pre class="whitespace-pre-wrap break-all">?{{ queryString }}</pre></div>
          <div><strong>onChange state</strong> (as it was before the change)<pre>{{ lastChange.state }}</pre></div>
        </template>
        <div><strong>paginationState</strong><pre>{{ paginationState }}</pre></div>
      </div>
    </div>
  `,
});

const meta = {
  title: 'Composables/useServerPagination',
  component: PaginatedTable,
  parameters: {
    docs: {
      description: {
        component:
          'Turns a table’s page and page-size changes into query params for the ' +
          'server. Give it the `pagination` prop your controller sent, spread ' +
          '`paginationConfig` into `useCraftTable`, read `paginationState` into ' +
          'the table’s state, and send `query` on with `Cp.$router.visit()` in ' +
          '`onChange`. A real visit remounts the page with fresh props; these ' +
          'stories apply the new state themselves. See the AdminTable Guide.',
      },
    },
  },
} satisfies Meta<typeof PaginatedTable>;

export default meta;

type Story = StoryObj<typeof meta>;

/**
 * With no `currentQuery`, the query starts from the page's own URL params.
 * Here those are the Storybook iframe's (`id`, `viewMode`); on a CP page
 * they're the page's own, such as an active search.
 */
export const Default: Story = {};

/**
 * The page number goes under `Craft.pageTrigger`, the site's `pageTrigger`
 * setting, which is what `TableRequest::page()` reads back.
 */
export const CustomPageTrigger: Story = {
  args: {pageTrigger: 'pg'},
};

/**
 * `currentQuery` replaces the page URL as the starting point, for a table
 * that isn't the page itself. Its params (`source`, `search`) carry over and
 * the iframe's don't.
 */
export const WithCurrentQuery: Story = {
  args: {withCurrentQuery: true},
};
