import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {ref} from 'vue';
import AdminTable from './AdminTable.vue';
import {TableSpacing, type TableSpacingValue} from '@/common/types';
import {
  createSampleTable,
  sampleEntries,
} from '@/modules/elements/fixtures/elements';

const meta = {
  title: 'CP/AdminTable/Docs',
  // The component is generic over its row type, which `Meta<typeof …>` can't
  // instantiate. Every story drives it through `render`, so the only thing the
  // cast costs is arg typing that nothing here uses.
  component: AdminTable as Meta['component'],
  args: {
    selectable: false,
    reorderable: false,
    readOnly: false,
    loading: false,
    padded: false,
    spacing: TableSpacing.Spacious,
  },
  argTypes: {
    spacing: {
      control: 'inline-radio',
      options: Object.values(TableSpacing),
    },
  },
  parameters: {
    docs: {
      description: {
        component:
          'A settings table with optional pagination, reordering, and a table-header slot.',
      },
    },
  },
} satisfies Meta;

export default meta;

interface AdminTableStoryArgs {
  title?: string;
  selectable?: boolean;
  reorderable?: boolean;
  readOnly?: boolean;
  loading?: boolean;
  padded?: boolean;
  spacing?: TableSpacingValue;
}

type Story = StoryObj<AdminTableStoryArgs>;

function render(args: NonNullable<Story['args']>) {
  return {
    components: {AdminTable},
    setup() {
      const table = createSampleTable();
      return {args, table};
    },
    template: '<AdminTable v-bind="args" :table="table" />',
  };
}

export const Default: Story = {
  render,
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
 * With `selectable`, every row gets a checkbox plus a select-all header.
 * Clicking a row selects it, shift-click selects a range, and a focused row
 * takes Space/Enter to toggle and Shift+Arrow to extend the selection.
 */
export const Selectable: Story = {
  args: {selectable: true},
  argTypes: {
    selectable: {control: false},
  },
  render,
};

/**
 * With `reorderable`, rows grow a drag handle. The table only emits `reorder`;
 * this story applies the move to its own data.
 */
export const Reorderable: Story = {
  args: {reorderable: true},
  argTypes: {
    reorderable: {control: false},
  },
  render: (args) => ({
    components: {AdminTable},
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
    template:
      '<AdminTable v-bind="args" :table="table" @reorder="onReorder" />',
  }),
};

/**
 * `readOnly` disables the selection checkboxes and drops the reorder handles.
 * When the prop is left unset it follows the page's `readOnly` prop.
 */
export const ReadOnly: Story = {
  args: {selectable: true, reorderable: true, readOnly: true},
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
    components: {AdminTable},
    setup() {
      const table = createSampleTable({data: []});
      return {args, table};
    },
    template: '<AdminTable v-bind="args" :table="table" />',
  }),
};

/** The `empty-row` slot replaces the default “No results” message. */
export const CustomEmptyState: Story = {
  render: (args) => ({
    components: {AdminTable},
    setup() {
      const table = createSampleTable({data: []});
      return {args, table};
    },
    template: `
      <AdminTable v-bind="args" :table="table">
        <template #empty-row>
          <craft-empty icon="file-lines" label="No entries yet"></craft-empty>
        </template>
      </AdminTable>
    `,
  }),
};

/** The `table-header` slot sits above the table, for search or filters. */
export const WithTableHeader: Story = {
  render: (args) => ({
    components: {AdminTable},
    setup() {
      const table = createSampleTable();
      return {args, table};
    },
    template: `
      <AdminTable v-bind="args" :table="table">
        <template #table-header>
          <craft-input label="Search" label-sr-only placeholder="Search"></craft-input>
        </template>
      </AdminTable>
    `,
  }),
};

export const Compact: Story = {
  args: {spacing: TableSpacing.Compact},
  render,
};

/**
 * `padded` insets the header and footer by the page container's padding, and
 * lets the table span it with each row's outer cells padded to match.
 */
export const Padded: Story = {
  args: {padded: true},
  render: (args) => ({
    components: {AdminTable},
    setup() {
      const table = createSampleTable({pageSize: 5});
      return {args, table};
    },
    template: `
      <div style="--cp-container-padding: var(--c-spacing-xl)">
        <p style="padding-inline: var(--cp-container-padding); margin-block-end: var(--c-spacing-md)">
          Page content aligned with the container.
        </p>
        <AdminTable v-bind="args" :table="table" :from="1" :to="5" :total="12" />
      </div>
    `,
  }),
};
