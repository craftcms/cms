import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {h} from 'vue';
import type {ColumnDef} from '@tanstack/vue-table';
import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
import type {CraftTableFeatures} from './craftTable';
import {useCraftTable} from './craftTable';
import {createCraftColumnHelper} from './createCraftColumnHelper';

interface Row {
  id: number;
  name: string;
  handle: string;
  dateCreated: string;
  lastUsed: {date: string};
  description: string;
  notes: string;
  enabled: boolean;
}

const rows: Array<Row> = [
  {
    id: 1,
    name: 'Homepage hero',
    handle: 'homepageHero',
    dateCreated: '2026-03-02T12:00:00Z',
    lastUsed: {date: '2026-10-01T12:00:00Z'},
    description: 'Shown <strong>above the fold</strong> on the homepage.',
    notes:
      'A long note that wraps onto several lines instead of stretching the column to fit it all on one.',
    enabled: true,
  },
  {
    id: 2,
    name: 'Footer promo',
    handle: 'footerPromo',
    dateCreated: '2026-05-18T12:00:00Z',
    lastUsed: {date: '2026-09-12T12:00:00Z'},
    description: 'Rotates through <em>seasonal</em> offers.',
    notes: 'Short note.',
    enabled: false,
  },
  {
    id: 3,
    name: 'Newsletter signup',
    handle: 'newsletterSignup',
    dateCreated: '2026-07-09T12:00:00Z',
    lastUsed: {date: '2026-10-08T12:00:00Z'},
    description: 'Plain text, no markup.',
    notes: 'Another short note.',
    enabled: true,
  },
];

const columnHelper = createCraftColumnHelper<Row>();

function render(
  columns: () => Array<ColumnDef<CraftTableFeatures, Row, any>>
): Story['render'] {
  return () => ({
    components: {AdminTable},
    setup() {
      const table = useCraftTable({
        data: rows,
        columns: columns(),
        getRowId: (row) => String(row.id),
      });
      return {table};
    },
    template: '<AdminTable :table="table" title="Promos" />',
  });
}

const meta = {
  title: 'Composables/createCraftColumnHelper',
  parameters: {
    docs: {
      description: {
        component:
          'TanStack’s column helper plus presets for the cells CP tables use most: ' +
          '`link`, `handle`, `date`, `html` and `actions`. Everything TanStack’s ' +
          'helper does (`accessor`, `display`, `group`, `columns`) still works. ' +
          'See the AdminTable Guide.',
      },
    },
  },
} satisfies Meta;

export default meta;

type Story = StoryObj;

/**
 * `link` renders the value as a bold `CpLink`, with `props(cell)` returning
 * its `href` and any other link props. Without `props` it's plain text, as in
 * the second column.
 */
export const Link: Story = {
  render: render(() => [
    columnHelper.link('name', {
      header: 'Name',
      props: ({row}) => ({href: `/admin/promos/${row.original.id}`}),
    }),
    columnHelper.link('handle', {header: 'Link without props'}),
  ]),
};

/** `handle` shows the value with a click-to-copy button. */
export const Handle: Story = {
  render: render(() => [
    columnHelper.accessor('name', {header: 'Name'}),
    columnHelper.handle('handle'),
  ]),
};

/** `date` formats an ISO string, or an object with a `date` string. */
export const DateColumn: Story = {
  render: render(() => [
    columnHelper.accessor('name', {header: 'Name'}),
    columnHelper.date('dateCreated', {header: 'Date Created'}),
    columnHelper.date('lastUsed', {header: 'Last Used ({date})'}),
  ]),
};

/** `html` renders the value as markup, for HTML the server built and escaped. */
export const Html: Story = {
  render: render(() => [
    columnHelper.accessor('name', {header: 'Name'}),
    columnHelper.html('description', {header: 'Description'}),
  ]),
};

/**
 * `actions` adds a right-aligned `actions` column with a screen-reader-only
 * header. Return each row's buttons.
 */
export const Actions: Story = {
  render: render(() => [
    columnHelper.accessor('name', {header: 'Name'}),
    columnHelper.handle('handle'),
    columnHelper.actions(({row}) => [
      h(DeleteButton, {
        label: `Delete ${row.original.name}`,
        onClick: () => console.log('delete', row.original.id),
      }),
    ]),
  ]),
};

/**
 * Column `meta`: `cellTag: 'th'` makes Name the row header, `trackSize` sizes
 * the Enabled column, `headerSrOnly` hides its header text, `headerTip` adds a
 * tooltip, and `wrap` lets Notes wrap.
 */
export const ColumnMeta: Story = {
  render: render(() => [
    columnHelper.accessor('name', {
      header: 'Name',
      meta: {cellTag: 'th', cellClass: 'font-bold'},
    }),
    columnHelper.accessor('enabled', {
      header: 'Enabled',
      meta: {trackSize: '60px', headerSrOnly: true},
      cell: ({getValue}) =>
        getValue() ? h('craft-icon', {name: 'check', label: 'Enabled'}) : null,
    }),
    columnHelper.accessor('notes', {
      header: 'Notes',
      meta: {wrap: true, headerTip: 'Internal notes, not shown publicly.'},
    }),
  ]),
};
