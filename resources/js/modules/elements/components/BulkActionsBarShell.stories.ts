import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {ref} from 'vue';
import BulkActionsBarShell from './BulkActionsBarShell.vue';
import ElementChips from './ElementChips.vue';
import {useSelectable} from '@/common/composables/useSelectable';
import type {ActionItems} from '@/common/types';
import {sampleElements} from '@/modules/elements/fixtures/elements';

interface BulkActionsBarShellStoryArgs {
  selectedCount: number;
}

const meta = {
  title: 'Elements/BulkActionsBar',
  component: BulkActionsBarShell,
  args: {selectedCount: 3},
  parameters: {
    docs: {
      description: {
        component:
          'The bar that replaces a list’s toolbar while items are selected. ' +
          'It shows how many are selected, a “Clear selection” button and an ' +
          '“Actions” menu. It renders nothing when `selectedCount` is 0.\n\n' +
          'Pass the menu’s items as `actions`. The menu is left out when there ' +
          'are none. The bar emits `clear` when “Clear selection” is pressed, ' +
          'and `performed` when an item with an `http` action succeeds, so you ' +
          'can refresh the list and clear the selection. Put menus that ' +
          'should always show, such as “Set status”, in the `status` slot, ' +
          'before “Actions”. The `center` slot sits in the middle of the bar, ' +
          'and the default slot goes after “Actions”.\n\n' +
          "```ts\nimport {BulkActionsBar} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof BulkActionsBarShell>;

export default meta;
type Story = StoryObj<BulkActionsBarShellStoryArgs>;

const actions: ActionItems = [
  {label: 'Duplicate', icon: 'clone', onClick: () => {}},
  {label: 'Move to…', icon: 'arrow-right', onClick: () => {}},
  {type: 'hr'},
  {label: 'Delete', icon: 'trash', variant: 'danger', onClick: () => {}},
];

/** Three items selected, with an Actions menu. */
export const Default: Story = {
  render: (args) => ({
    components: {BulkActionsBarShell},
    setup() {
      return {args, actions};
    },
    template: `
      <BulkActionsBarShell
        :selected-count="args.selectedCount"
        :actions="actions"
      />
    `,
  }),
};

/** A pinned menu in the `status` slot, and page controls in `center`. */
export const WithStatusAndCenter: Story = {
  render: (args) => ({
    components: {BulkActionsBarShell},
    setup() {
      return {args, actions};
    },
    template: `
      <BulkActionsBarShell
        :selected-count="args.selectedCount"
        :actions="actions"
      >
        <template #status>
          <craft-button type="button" size="small">Set status</craft-button>
        </template>
        <template #center>
          <span>Page 1 of 4</span>
        </template>
      </BulkActionsBarShell>
    `,
  }),
};

/** Wired to a list: select chips to show the bar, and clear them from it. */
export const WithSelection: Story = {
  render: () => ({
    components: {BulkActionsBarShell, ElementChips},
    setup() {
      const items = ref([...sampleElements]);
      const selection = useSelectable({
        ids: () => items.value.map((element) => element.id),
      });

      const listActions: ActionItems = [
        {
          label: 'Delete',
          icon: 'trash',
          variant: 'danger',
          onClick: () => {
            const selected = new Set(selection.selectedIds.value);
            items.value = items.value.filter(({id}) => !selected.has(id));
            selection.clear();
          },
        },
      ];

      return {items, selection, listActions};
    },
    template: `
      <div style="display: grid; gap: 1rem; max-width: 40rem">
        <div style="min-height: 2rem">
          <BulkActionsBarShell
            :selected-count="selection.selectedIds.value.length"
            :actions="listActions"
            @clear="selection.clear()"
          />
        </div>
        <ElementChips :data="items" :selection="selection" selectable />
      </div>
    `,
  }),
};
