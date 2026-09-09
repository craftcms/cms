import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {ref} from 'vue';
import type {ActionItem} from '@/common/types';
import CustomSourceList from './CustomSourceList.vue';

/**
 * A row, in the shape a caller would hold it. The component never sees this
 * type — it reads every row through the callbacks below — so the stories use
 * whatever shape reads clearly.
 */
interface Row {
  id: string;
  label: string;
  icon?: string;
  /** Stands in for a source with no key, which can't be selected. */
  unselectable?: boolean;
}

const SOURCES: Row[] = [
  {id: 'section:all', label: 'All entries'},
  {id: 'heading:channels', label: 'Channels'},
  {id: 'section:channel', label: 'Channel'},
  {id: 'section:test', label: 'Test'},
];

const PAGES: Row[] = [
  {id: 'Entries', label: 'Entries', icon: 'newspaper'},
  {id: 'Archive', label: 'Archive', icon: 'box-archive'},
];

/**
 * Wires the component up the way a sidebar does: it's controlled, so selection
 * and order live out here. Without that a story would render but nothing would
 * respond to a click or a drag.
 */
function sidebar(rows: Row[], template: string) {
  return () => ({
    components: {CustomSourceList},
    setup() {
      const items = ref<Row[]>(rows.map((row) => ({...row})));
      const selected = ref<string | null>(
        items.value.find((row) => !row.unselectable)?.id ?? null
      );

      return {
        items,
        selected,
        itemId: (row: Row) => row.id,
        label: (row: Row) => row.label,
        icon: (row: Row) => row.icon ?? null,
        unselectable: (row: Row) => row.unselectable === true,
        actions: (row: Row): ActionItem[] =>
          row.unselectable
            ? []
            : [
                {label: 'Settings', onClick: () => undefined},
                {label: 'Delete', variant: 'danger', onClick: () => undefined},
              ],
        onSelect: (id: string) => {
          selected.value = id;
        },
        onReorder: (from: number, to: number) => {
          const [moved] = items.value.splice(from, 1);
          if (moved) items.value.splice(to, 0, moved);
        },
      };
    },
    template: `<div style="max-width: 220px">${template}</div>`,
  });
}

const LIST = `
  <CustomSourceList
    :items="items"
    :item-id="itemId"
    :label="label"
    :selected="selected"
    :actions="actions"
    @select="onSelect"
    @reorder="onReorder"
  />
`;

const meta: Meta = {
  title: 'Elements/CustomSourceList',
  // The component is generic over its item type, which `Meta<typeof …>` can't
  // instantiate. Every story drives it through `render`, so the only thing the
  // cast costs is arg typing that nothing here uses.
  component: CustomSourceList as Meta['component'],
  parameters: {
    docs: {
      description: {
        component:
          'The reorderable row list behind the customize-sources modal, used ' +
          'for both the pages and the sources sidebar. It owns the row markup, ' +
          'drag and keyboard reordering, and the selected state; a caller ' +
          'describes each row through callbacks — `itemId`, `label`, and ' +
          'optionally `icon`, `disabled` and `actions`.\n\n' +
          'It is controlled: `select` and `reorder` are emitted, and the ' +
          'caller owns `items` and `selected`. Every story here holds that ' +
          'state so the rows actually respond.',
      },
    },
  },
};

export default meta;
type Story = StoryObj<typeof meta>;

/** Click a row to select it; drag a handle or use its reorder buttons to move it. */
export const Default: Story = {
  render: sidebar(SOURCES, LIST),
};

/**
 * `icon` leads the row — how the pages sidebar shows a page's icon.
 *
 * The glyph itself won't resolve here: `PageIcon` fetches through the legacy
 * `Craft.ui.icon` global, which Storybook doesn't boot, and the composable
 * swallows the failure. The row layout and spacing are still representative.
 */
export const WithIcons: Story = {
  render: sidebar(
    PAGES,
    `
  <CustomSourceList
    :items="items"
    :item-id="itemId"
    :label="label"
    :icon="icon"
    :selected="selected"
    :actions="actions"
    @select="onSelect"
    @reorder="onReorder"
  />
`
  ),
};

/**
 * A blank label renders as an italic “(blank)”, and `disabled` marks a row that
 * can't be selected — the customize-sources modal uses that for the separator
 * heading `ElementSources` synthesizes, which has no key to address.
 */
export const BlankAndUnselectable: Story = {
  render: sidebar(
    [
      {id: 'section:all', label: 'All entries'},
      {id: 'heading:blank', label: '   '},
      {id: 'unkeyed-2', label: '', unselectable: true},
      {id: 'section:test', label: 'Test'},
    ],
    `
  <CustomSourceList
    :items="items"
    :item-id="itemId"
    :label="label"
    :selected="selected"
    :disabled="unselectable"
    :actions="actions"
    @select="onSelect"
    @reorder="onReorder"
  />
`
  ),
};

/** Nothing to reorder, so no drag handles are rendered. */
export const SingleRow: Story = {
  render: sidebar([{id: 'Entries', label: 'Entries'}], LIST),
};

/** Long labels truncate rather than widening the sidebar. */
export const LongLabels: Story = {
  render: sidebar(
    [
      {id: 'a', label: 'All entries'},
      {id: 'b', label: 'Quarterly editorial calendar and planning'},
      {id: 'c', label: 'Archived press releases, 2019 onward'},
    ],
    LIST
  ),
};
