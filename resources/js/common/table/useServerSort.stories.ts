import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {computed, defineComponent, ref} from 'vue';
import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
import type {SortItem} from '@/common/types';
import {useCraftTable} from './craftTable';
import {createCraftColumnHelper} from './createCraftColumnHelper';
import {useServerSort} from './useServerSort';
import {
  queryWidgets,
  toQueryString,
  type Widget,
} from '@/modules/admin-table/fixtures/widgets';

/**
 * An AdminTable sorted through `useServerSort`, next to what the composable
 * hands `onChange`. A local stand-in plays the controller, and the story
 * applies the new state itself, as a page's remount would.
 */
const SortedTable = defineComponent({
  components: {AdminTable},
  props: {
    multiSort: {type: Boolean, default: true},
    typeSortable: {type: Boolean, default: true},
  },
  setup(props) {
    const columnHelper = createCraftColumnHelper<Widget>();
    const columns = columnHelper.columns([
      columnHelper.accessor('name', {header: 'Name'}),
      columnHelper.handle('handle'),
      columnHelper.accessor('type', {
        header: 'Type',
        enableSorting: props.typeSortable,
      }),
      columnHelper.date('dateCreated', {header: 'Date Created'}),
    ]);

    const sort = ref<Array<SortItem>>([{field: 'name', direction: 'asc'}]);
    const rows = computed(
      () => queryWidgets({page: 1, perPage: 8, sort: sort.value}).data
    );
    const lastQuery = ref<Record<string, unknown> | null>(null);

    const {sortingState, sortingConfig} = useServerSort({
      initialState: sort.value,
      currentQuery: () => ({}),
      onChange: ({query}) => {
        lastQuery.value = query;
        sort.value = Object.values(query.sort as Record<number, SortItem>);
        sortingState.value = sort.value.map((s) => ({
          id: s.field,
          desc: s.direction === 'desc',
        }));
      },
    });

    const table = useCraftTable({
      get data() {
        return rows.value;
      },
      columns,
      getRowId: (row) => String(row.id),
      state: {
        get sorting() {
          return sortingState.value;
        },
      },
      ...sortingConfig,
      enableMultiSort: props.multiSort,
    });

    const queryString = computed(() =>
      lastQuery.value ? toQueryString(lastQuery.value) : ''
    );

    return {table, lastQuery, queryString, sortingState};
  },
  template: `
    <div class="flex flex-col gap-4">
      <AdminTable :table="table" title="Widgets" />
      <div class="text-xs flex flex-col gap-2">
        <p v-if="!lastQuery">Click a column header to see what <code>onChange</code> receives.</p>
        <template v-else>
          <div><strong>onChange query</strong><pre>{{ lastQuery }}</pre></div>
          <div><strong>As a URL</strong><pre class="whitespace-pre-wrap break-all">?{{ queryString }}</pre></div>
        </template>
        <div><strong>sortingState</strong><pre>{{ sortingState }}</pre></div>
      </div>
    </div>
  `,
});

const meta = {
  title: 'Composables/useServerSort',
  component: SortedTable,
  parameters: {
    docs: {
      description: {
        component:
          'Turns a table’s sort changes into query params for the server. Give it ' +
          'the `sort` prop your controller sent, spread `sortingConfig` into ' +
          '`useCraftTable`, read `sortingState` into the table’s state, and send ' +
          '`query` on with `Cp.$router.visit()` in `onChange`. Columns are only ' +
          'sortable once this is wired up, and every sort change goes back to ' +
          'page 1. These stories show the first page of results. See the ' +
          'AdminTable Guide.',
      },
    },
  },
} satisfies Meta<typeof SortedTable>;

export default meta;

type Story = StoryObj<typeof meta>;

/**
 * Click a header to sort by it, again to reverse it. Shift-click another
 * header to add it as a secondary sort: `sort[0]` then `sort[1]`.
 */
export const Default: Story = {};

/** With `enableMultiSort: false`, a shift-click replaces the sort too. */
export const SingleColumn: Story = {
  args: {multiSort: false},
};

/** `enableSorting: false` on a column keeps it out of sorting. */
export const UnsortableColumn: Story = {
  args: {typeSortable: false},
};
