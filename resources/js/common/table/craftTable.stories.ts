import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {computed, defineComponent, ref} from 'vue';
import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
import {useCraftTable} from './craftTable';
import {createCraftColumnHelper} from './createCraftColumnHelper';
import {widgets, type Widget} from '@/modules/admin-table/fixtures/widgets';

const columnHelper = createCraftColumnHelper<Widget>();
const columns = columnHelper.columns([
  columnHelper.accessor('name', {header: 'Name'}),
  columnHelper.handle('handle'),
  columnHelper.accessor('type', {header: 'Type'}),
  columnHelper.date('dateCreated', {header: 'Date Created'}),
]);

const columnIds = ['name', 'handle', 'type', 'dateCreated'];

/**
 * An AdminTable built with `useCraftTable`, with toggles for its column
 * visibility and a readout of its row selection.
 */
const CraftTable = defineComponent({
  components: {AdminTable},
  props: {
    selectable: {type: Boolean, default: false},
    showColumnToggles: {type: Boolean, default: false},
    // Stands in for rows the server says can't be selected.
    lockGizmos: {type: Boolean, default: false},
  },
  setup(props) {
    const visibility = ref<Record<string, boolean>>({});

    const table = useCraftTable({
      data: widgets.slice(0, 6),
      columns,
      getRowId: (row) => String(row.id),
      enableRowSelection: (row) =>
        !props.lockGizmos || row.original.type !== 'Gizmo',
      state: {
        get columnVisibility() {
          return visibility.value;
        },
      },
    });

    const selected = computed(() =>
      table.getSelectedRowModel().rows.map((row) => row.original.name)
    );

    function toggleColumn(id: string, visible: boolean) {
      visibility.value = {...visibility.value, [id]: visible};
    }

    return {table, selected, visibility, columnIds, toggleColumn};
  },
  template: `
    <div class="flex flex-col gap-4">
      <div v-if="showColumnToggles" class="flex gap-4 text-sm">
        <label v-for="id in columnIds" :key="id" class="flex items-center gap-1">
          <input
            type="checkbox"
            :checked="visibility[id] !== false"
            @change="toggleColumn(id, $event.target.checked)"
          />
          {{ id }}
        </label>
      </div>
      <AdminTable :table="table" title="Widgets" :selectable="selectable" />
      <p v-if="selectable" class="text-xs">
        <strong>getSelectedRowModel()</strong>:
        {{ selected.length ? selected.join(', ') : 'nothing selected' }}
      </p>
    </div>
  `,
});

const meta = {
  title: 'Composables/useCraftTable',
  component: CraftTable,
  parameters: {
    docs: {
      description: {
        component:
          'Creates a TanStack table with the features `AdminTable` relies on: ' +
          'row selection, sorting, pagination, and column visibility, ordering ' +
          'and sizing. Use it in place of TanStack’s `useTable`. Sorting and ' +
          'paging happen on the server, so no client-side row models are ' +
          'registered and sorting is off until `useServerSort` turns it on. ' +
          'See the AdminTable Guide.',
      },
    },
  },
} satisfies Meta<typeof CraftTable>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Column headers aren't sortable until `useServerSort` is wired up. */
export const Default: Story = {};

/**
 * Column visibility is table state. Read it from your own ref through a
 * `columnVisibility` getter; a column that's missing or `true` is shown.
 */
export const ColumnVisibility: Story = {
  args: {showColumnToggles: true},
};

/**
 * The table owns the selection. Read it back with `getSelectedRowModel()`, and
 * give the table a `getRowId` so a selection stays with its rows.
 */
export const Selection: Story = {
  args: {selectable: true},
};

/** `enableRowSelection` as a function vetoes rows: Gizmos can't be selected. */
export const UnselectableRows: Story = {
  args: {selectable: true, lockGizmos: true},
};
