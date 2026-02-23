import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {
  createColumnHelper,
  getCoreRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from '@tanstack/vue-table';
import {h, ref} from 'vue';

import AdminTable from './AdminTable.vue';

type Person = {
  name: string;
  email: string;
  role: string;
};

const columnHelper = createColumnHelper<Person>();
const columns = [
  columnHelper.accessor('name', {
    header: 'Name',
    cell: (info) => info.getValue(),
  }),
  columnHelper.accessor('email', {
    header: 'Email',
    cell: (info) => info.getValue(),
  }),
  columnHelper.accessor('role', {
    header: 'Role',
    cell: (info) => info.getValue(),
  }),
];

const sampleData: Person[] = [
  {name: 'Alice Johnson', email: 'alice@example.com', role: 'Admin'},
  {name: 'Bob Smith', email: 'bob@example.com', role: 'Editor'},
  {name: 'Carol White', email: 'carol@example.com', role: 'Author'},
  {name: 'Dave Brown', email: 'dave@example.com', role: 'Editor'},
  {name: 'Eve Davis', email: 'eve@example.com', role: 'Admin'},
];

function createTable(data: Person[], pageSize = 10) {
  return useVueTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: {
      pagination: {pageSize},
    },
  });
}

const meta = {
  title: 'Components/AdminTable',
  component: AdminTable,
  tags: ['autodocs'],
} satisfies Meta<typeof AdminTable>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  render: () => ({
    components: {AdminTable},
    setup() {
      const table = createTable(sampleData);
      return {table};
    },
    template: `<AdminTable :table="table" :reorderable="false" :selectable="false" />`,
  }),
};

export const ReadOnly: Story = {
  render: () => ({
    components: {AdminTable},
    setup() {
      const table = createTable(sampleData);
      return {table};
    },
    template: `<AdminTable :table="table" :read-only="true" :reorderable="false" :selectable="false" />`,
  }),
};

export const Relaxed: Story = {
  render: () => ({
    components: {AdminTable},
    setup() {
      const table = createTable(sampleData);
      return {table};
    },
    template: `<AdminTable :table="table" :reorderable="false" :selectable="false" spacing="relaxed" />`,
  }),
};

export const WithPagination: Story = {
  render: () => ({
    components: {AdminTable},
    setup() {
      const data = Array.from({length: 25}, (_, i) => ({
        name: `User ${i + 1}`,
        email: `user${i + 1}@example.com`,
        role: i % 3 === 0 ? 'Admin' : i % 3 === 1 ? 'Editor' : 'Author',
      }));
      const table = createTable(data, 5);
      return {table, from: 1, to: 5, total: 25};
    },
    template: `<AdminTable :table="table" :reorderable="false" :selectable="false" :from="from" :to="to" :total="total" />`,
  }),
};
