import type {Meta, StoryObj} from '@storybook/vue3-vite';
import BaseElementIndex from './BaseElementIndex.vue';
import DataTable from './DataTable.vue';
import {
  createSampleTable,
  sampleActions,
} from '@/modules/elements/fixtures/elements';

const meta = {
  title: 'Elements/BaseElementIndex',
  component: BaseElementIndex,
  parameters: {
    docs: {
      description: {
        component:
          'The shared shell around every element index view. It owns the ' +
          'header/body/footer chrome, the pagination footer, the bulk-actions ' +
          'bar, and an ARIA live region that announces loading and selection ' +
          'changes. Put a toolbar in the `header` slot and a view body ' +
          '(`DataTable` or `ElementCards`) in the `body` slot.',
      },
    },
  },
} satisfies Meta<typeof BaseElementIndex>;

export default meta;
type Story = StoryObj<{loading?: boolean}>;

/**
 * A paginated index: the footer shows the displayed-rows text, the pager, and
 * the page-size select. Pagination here is client-side for demo purposes — in
 * the control panel the table is server-paginated and these controls trigger
 * Inertia visits.
 */
export const Default: Story = {
  render: (args) => ({
    components: {BaseElementIndex, DataTable},
    setup() {
      const table = createSampleTable({pageSize: 5});
      return {args, table};
    },
    template: `
      <BaseElementIndex
        v-bind="args"
        :table="table"
        :from="1"
        :to="5"
        :total="12"
        enable-adjust-page-size
        :page-size-options="[5, 10, 50]"
      >
        <template #header>
          <div class="cp:flex cp:gap-2 cp:items-center">
            <craft-input label="Search" label-sr-only placeholder="Search…" />
          </div>
        </template>
        <template #body>
          <DataTable :table="table" />
        </template>
      </BaseElementIndex>
    `,
  }),
};

/**
 * With `selectable` and a set of serialized bulk actions, selecting rows swaps
 * the footer for the bulk-actions bar. The demo actions point at inert URLs —
 * in the control panel they POST to `element-indexes/perform-action`.
 */
export const WithBulkActions: Story = {
  render: (args) => ({
    components: {BaseElementIndex, DataTable},
    setup() {
      const table = createSampleTable({pageSize: 8});
      return {args, table, actions: sampleActions};
    },
    template: `
      <BaseElementIndex
        v-bind="args"
        :table="table"
        selectable
        :actions="actions"
        element-type="demo\\\\Entry"
        :from="1"
        :to="8"
        :total="12"
      >
        <template #body>
          <DataTable :table="table" selectable />
        </template>
      </BaseElementIndex>
    `,
  }),
};

/**
 * While `loading`, the body gets `aria-busy` and the live region announces the
 * state to screen readers.
 */
export const Loading: Story = {
  render: (args) => ({
    components: {BaseElementIndex, DataTable},
    setup() {
      const table = createSampleTable();
      return {args, table};
    },
    template: `
      <BaseElementIndex v-bind="args" :table="table" loading>
        <template #body>
          <DataTable :table="table" loading />
        </template>
      </BaseElementIndex>
    `,
  }),
};
