import type {Meta, StoryObj} from '@storybook/vue3-vite';
import AdminTable from './AdminTable.vue';
import {createSampleTable} from '@/modules/elements/fixtures/elements';

const meta = {
  title: 'Elements/AdminTable',
  component: AdminTable,
  parameters: {
    docs: {
      description: {
        component:
          'A settings table with optional pagination, reordering, and a table-header slot.',
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
