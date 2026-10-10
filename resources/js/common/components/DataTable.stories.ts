import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {createColumnHelper} from '@tanstack/vue-table';
import {ref} from 'vue';
import DataTable from './DataTable.vue';
import {
  type CraftTableFeatures,
  useCraftTable,
} from '@/common/table/craftTable';
import {TableSpacing, type TableSpacingValue} from '@/common/types';
import {
  createSampleTable,
  type SampleEntry,
  sampleEntries,
} from '@/modules/elements/fixtures/elements';

const meta = {
  title: 'CP/DataTable',
  // The component is generic over its row type, which `Meta<typeof …>` can't
  // instantiate. Every story drives it through `render`, so the only thing the
  // cast costs is arg typing that nothing here uses.
  component: DataTable as Meta['component'],
  args: {
    title: 'Entries',
    reorderable: false,
    interactionsDisabled: false,
    readOnly: false,
    loading: false,
    layout: 'auto',
    spacing: undefined,
    withBottomBorder: true,
    flush: false,
  },
  argTypes: {
    layout: {control: 'inline-radio', options: ['auto', 'fixed']},
    spacing: {
      control: 'inline-radio',
      options: [undefined, ...Object.values(TableSpacing)],
    },
  },
  parameters: {
    docs: {
      description: {
        component:
          'The table every CP listing renders through. It draws headers, ' +
          'rows, sort buttons, drag-to-reorder and the loading/empty states ' +
          'from a TanStack table instance, and leaves selection, pagination ' +
          'and chrome to wrappers like `AdminTable` and `ElementTable`.',
      },
    },
  },
} satisfies Meta;

export default meta;

interface DataTableStoryArgs {
  title?: string;
  reorderable?: boolean;
  interactionsDisabled?: boolean;
  readOnly?: boolean;
  loading?: boolean;
  layout?: 'auto' | 'fixed';
  spacing?: TableSpacingValue;
  withBottomBorder?: boolean;
  flush?: boolean;
}

type Story = StoryObj<DataTableStoryArgs>;

function render(args: NonNullable<Story['args']>) {
  return {
    components: {DataTable},
    setup() {
      const table = createSampleTable();
      return {args, table};
    },
    template: '<DataTable v-bind="args" :table="table" />',
  };
}

/**
 * The sample table sorts on the client, so clicking a column header reorders
 * the rows. CP tables sort on the server and opt in per column.
 */
export const Default: Story = {
  render,
};

/**
 * With `reorderable`, rows grow a drag handle and keyboard reorder buttons.
 * The table only reports the move; this story applies it to its own data.
 */
export const Reorderable: Story = {
  args: {reorderable: true},
  argTypes: {
    reorderable: {control: false},
  },
  render: (args) => ({
    components: {DataTable},
    setup() {
      const data = ref(sampleEntries.slice(0, 6));
      const table = createSampleTable({data});
      function onReorder(start: number, finish: number) {
        const next = [...data.value];
        const [moved] = next.splice(start, 1);
        if (moved) next.splice(finish, 0, moved);
        data.value = next;
      }
      return {args, table, onReorder};
    },
    template: '<DataTable v-bind="args" :table="table" @reorder="onReorder" />',
  }),
};

/**
 * Keeps the sort buttons and reorder handles in place but disabled, so the
 * layout holds still while a request runs.
 */
export const InteractionsDisabled: Story = {
  args: {reorderable: true, interactionsDisabled: true},
  render,
};

/** `readOnly` drops the reorder column even when `reorderable` is set. */
export const ReadOnly: Story = {
  args: {reorderable: true, readOnly: true},
  render,
};

export const Loading: Story = {
  args: {loading: true},
  argTypes: {
    loading: {control: false},
  },
  render,
};

export const Empty: Story = {
  render: (args) => ({
    components: {DataTable},
    setup() {
      const table = createSampleTable({data: []});
      return {args, table};
    },
    template: '<DataTable v-bind="args" :table="table" />',
  }),
};

/** The `empty-row` slot replaces the default “No results” message. */
export const CustomEmptyState: Story = {
  render: (args) => ({
    components: {DataTable},
    setup() {
      const table = createSampleTable({data: []});
      return {args, table};
    },
    template: `
      <DataTable v-bind="args" :table="table">
        <template #empty-row>
          <craft-empty icon="file-lines" label="No entries yet"></craft-empty>
        </template>
      </DataTable>
    `,
  }),
};

export const Compact: Story = {
  args: {spacing: TableSpacing.Compact},
  render,
};

export const Spacious: Story = {
  args: {spacing: TableSpacing.Spacious},
  render,
};

/**
 * `flush` drops the inline padding at each end of a row, so cell content lines
 * up with the edges of whatever contains the table.
 */
export const Flush: Story = {
  args: {flush: true},
  render: (args) => ({
    components: {DataTable},
    setup() {
      const table = createSampleTable();
      return {args, table};
    },
    template: `
      <div style="padding: var(--c-spacing-lg); outline: 1px dashed var(--c-color-neutral-border-normal)">
        <p style="margin-block-end: var(--c-spacing-md)">Text aligned with the container.</p>
        <DataTable v-bind="args" :table="table" />
      </div>
    `,
  }),
};

/** Drops the border under the last row, for tables sitting in a bordered box. */
export const WithoutBottomBorder: Story = {
  args: {withBottomBorder: false},
  render,
};

const columnHelper = createColumnHelper<CraftTableFeatures, SampleEntry>();

const metaColumns = columnHelper.columns([
  columnHelper.accessor('id', {
    header: 'ID',
    meta: {cellTag: 'th', trackSize: '4rem'},
  }),
  columnHelper.accessor('title', {
    header: 'Title',
    meta: {wrap: true, trackSize: 'minmax(0, 2fr)'},
  }),
  columnHelper.accessor('section', {
    header: 'Section',
    meta: {headerTip: 'The section each entry belongs to.'},
  }),
  columnHelper.accessor('postDate', {
    header: 'Post Date',
    meta: {columnClass: 'text-end', headerClass: 'text-end'},
  }),
  columnHelper.display({
    id: 'actions',
    header: 'Actions',
    meta: {headerSrOnly: true, trackSize: 'max-content'},
    cell: () => '…',
  }),
]);

/**
 * Columns can tune their own rendering through `meta`: `trackSize` sets the
 * grid track, `wrap` lets long values wrap, `headerTip` adds an info icon,
 * `headerSrOnly` hides a header visually, `cellTag: 'th'` makes a row header,
 * and the `*Class` options style the header, cells, or both.
 */
export const ColumnMeta: Story = {
  render: (args) => ({
    components: {DataTable},
    setup() {
      const table = useCraftTable({
        data: sampleEntries.slice(0, 5),
        columns: metaColumns,
        getRowId: (row) => String(row.id),
      });
      return {args, table};
    },
    template: '<DataTable v-bind="args" :table="table" />',
  }),
};
