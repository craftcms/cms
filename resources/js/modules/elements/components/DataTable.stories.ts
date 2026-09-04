import type {Meta, StoryObj} from '@storybook/vue3-vite';
import DataTable from './DataTable.vue';
import {
  createSampleTable,
  sampleEntries,
} from '@/modules/elements/fixtures/elements';

const meta = {
  title: 'Elements/DataTable',
  component: DataTable,
  args: {
    loading: false,
  },
  argTypes: {
    reorderable: {control: 'boolean'},
    selectable: {control: 'boolean'},
    loading: {control: 'boolean'},
  },
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
interface DataTableStoryArgs {
  selectable?: boolean;
  reorderable?: boolean;
  loading?: boolean;
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

export const Default: Story = {
  render,
};

/**
 * With `selectable`, every row gets a checkbox plus a select-all header.
 * Selection supports shift-click range selection, and keyboard control on a
 * focused row: Space/Enter toggles, Arrow Up/Down moves, Shift+Arrow extends.
 * `selectable`'s control is disabled so this story always demonstrates it.
 */
export const Selectable: Story = {
  args: {selectable: true},
  argTypes: {
    selectable: {control: false},
  },
  render,
};

/**
 * With `reorderable`, rows grow a drag handle. The component only reports the
 * move — persist it by listening for `@reorder="(start, finish) => …"`.
 * `reorderable`'s control is disabled so this story always demonstrates it.
 */
export const Reorderable: Story = {
  args: {reorderable: true},
  argTypes: {
    reorderable: {control: false},
  },
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
      const table = createSampleTable({data: sampleEntries.slice(0, 0)});
      return {args, table};
    },
    template: '<DataTable v-bind="args" :table="table" />',
  }),
};
