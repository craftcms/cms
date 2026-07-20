import type {Meta, StoryObj} from '@storybook/vue3-vite';
import type {Table} from '@tanstack/vue-table';
import DataTable from './DataTable.vue';
import {
  createSampleTable,
  sampleEntries,
} from '@/modules/elements/fixtures/elements';

const meta = {
  title: 'Elements/DataTable',
  component: DataTable,
  parameters: {
    docs: {
      description: {
        component:
          'The bare table body of an element index — headers, rows, sorting, ' +
          'row selection, drag-to-reorder, and the loading/empty states. It ' +
          'renders no shell or footer; compose it inside `BaseElementIndex` ' +
          '(or use `AdminTable`, which does that for you).',
      },
    },
  },
} satisfies Meta<typeof DataTable>;

export default meta;
type Story = StoryObj<typeof meta>;

// Each story builds its real table inside setup(); this satisfies the
// required `table` prop in `args`, which the template's :table overrides.
const tablePlaceholder = null as unknown as Table<any>;

function render(extraProps: Record<string, unknown> = {}) {
  return (args: Record<string, unknown>) => ({
    components: {DataTable},
    setup() {
      const table = createSampleTable();
      return {args: {...args, ...extraProps}, table};
    },
    template: '<DataTable v-bind="args" :table="table" />',
  });
}

export const Default: Story = {
  render: render(),
  args: {table: tablePlaceholder},
};

/**
 * With `selectable`, every row gets a checkbox plus a select-all header.
 * Selection supports shift-click range selection, and keyboard control on a
 * focused row: Space/Enter toggles, Arrow Up/Down moves, Shift+Arrow extends.
 */
export const Selectable: Story = {
  render: render({selectable: true}),
  args: {table: tablePlaceholder},
};

/**
 * With `reorderable`, rows grow a drag handle. The component only reports the
 * move — persist it by listening for `@reorder="(start, finish) => …"`.
 */
export const Reorderable: Story = {
  render: render({reorderable: true}),
  args: {table: tablePlaceholder},
};

export const Loading: Story = {
  render: render({loading: true}),
  args: {table: tablePlaceholder},
};

export const Empty: Story = {
  render: (args) => ({
    components: {DataTable},
    setup() {
      const table = createSampleTable({data: sampleEntries.slice(0, 0)});
      return {args, table};
    },
    template: '<DataTable v-bind="args" :table="table" />',
  }),
  args: {table: tablePlaceholder},
};
