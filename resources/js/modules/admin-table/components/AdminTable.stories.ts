import type {Meta, StoryObj} from '@storybook/vue3-vite';
import AdminTable from './AdminTable.vue';
import {
  createSampleTable,
  sampleActions,
} from '@/modules/elements/fixtures/elements';

const meta = {
  title: 'Elements/AdminTable',
  component: AdminTable,
  parameters: {
    docs: {
      description: {
        component:
          'A one-stop table for admin screens: `BaseElementIndex` + ' +
          '`DataTable` pre-composed, so a page only supplies a TanStack table ' +
          'instance and (optionally) pagination figures, bulk actions, and a ' +
          'toolbar via the `table-header` slot. Most settings index pages use ' +
          'this. Reach for the pieces individually only when you need a ' +
          'different body, like the cards view.',
      },
    },
  },
} satisfies Meta<typeof AdminTable>;

export default meta;
type Story = StoryObj<{title?: string}>;

export const Default: Story = {
  render: (args) => ({
    components: {AdminTable},
    setup() {
      const table = createSampleTable();
      return {args, table};
    },
    template: '<AdminTable v-bind="args" :table="table" />',
  }),
};

/**
 * Pagination is driven by the table instance; `from`/`to`/`total` feed the
 * displayed-rows text and `enableAdjustPageSize` adds the page-size select.
 */
export const Paginated: Story = {
  render: (args) => ({
    components: {AdminTable},
    setup() {
      const table = createSampleTable({pageSize: 5});
      return {args, table};
    },
    template: `
      <AdminTable
        v-bind="args"
        :table="table"
        :from="1"
        :to="5"
        :total="12"
        enable-adjust-page-size
        :page-size-options="[5, 10, 50]"
      />
    `,
  }),
};

/**
 * Selection and bulk actions flow straight through to the shell — select a
 * couple of rows and the footer becomes the bulk-actions bar.
 */
export const SelectableWithActions: Story = {
  render: (args) => ({
    components: {AdminTable},
    setup() {
      const table = createSampleTable();
      return {args, table, actions: sampleActions};
    },
    template: `
      <AdminTable
        v-bind="args"
        :table="table"
        selectable
        :actions="actions"
        element-type="demo\\\\Entry"
      />
    `,
  }),
};
