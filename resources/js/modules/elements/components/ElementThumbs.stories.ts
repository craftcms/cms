import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {computed, ref} from 'vue';
import ElementThumbs from './ElementThumbs.vue';
import {useSelectable} from '@/common/composables/useSelectable';
import {sampleAssets} from '@/modules/elements/fixtures/elements';

interface ElementThumbsStoryArgs {
  selectable?: boolean;
  selectAll?: boolean;
  sortable?: boolean;
  readOnly?: boolean;
  loading?: boolean;
  thumbSize?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
  placeholders?: number;
}

const meta = {
  title: 'Elements/ElementThumbs',
  component: ElementThumbs,
  args: {
    selectable: false,
    selectAll: true,
    sortable: false,
    readOnly: false,
    loading: false,
    thumbSize: 'lg',
    placeholders: 0,
  },
  argTypes: {
    thumbSize: {
      control: 'inline-radio',
      options: ['sm', 'md', 'lg', 'xl', '2xl'],
    },
  },
  parameters: {
    docs: {
      description: {
        component:
          'Draws elements as a grid of thumbnail tiles. This is the thumbs ' +
          'view of the element index and of relation fields. Each tile shows ' +
          'the element’s `thumbHtml` and its label, or an asset icon when it ' +
          'has no thumbnail.\n\n' +
          'Pass the elements as `data`, in display order. A tile with a ' +
          '`url` links its label there.\n\n' +
          'The grid doesn’t keep its own selection. Create one with ' +
          '`useSelectable` and pass it as `selection`. With `selectable`, a ' +
          'click on a tile selects it, and arrow keys, Space and Shift move ' +
          'and extend the selection. With `sortable`, the grid emits ' +
          '`reorder` with the old and new index. A double-click emits ' +
          '`edit`. The `actions` slot adds controls to each tile.\n\n' +
          'Plugins draw tiles through `ElementList` with the `thumbs` view ' +
          'mode.',
      },
    },
  },
} satisfies Meta<typeof ElementThumbs>;

export default meta;
type Story = StoryObj<ElementThumbsStoryArgs>;

function render(args: NonNullable<Story['args']>) {
  return {
    components: {ElementThumbs},
    setup() {
      const items = ref([...sampleAssets]);
      const selection = useSelectable({
        ids: () => items.value.map((asset) => asset.id),
      });
      const selectedCount = computed(() => selection.selectedIds.value.length);

      function reorder(from: number, to: number) {
        const [moved] = items.value.splice(from, 1);
        if (moved) {
          items.value.splice(to, 0, moved);
        }
      }

      return {args, items, selection, selectedCount, reorder};
    },
    template: `
      <ElementThumbs
        v-bind="args"
        :data="items"
        :selection="selection"
        @reorder="reorder"
      />
      <p v-if="args.selectable" style="margin-top: 1rem">
        {{ selectedCount }} selected
      </p>
    `,
  };
}

/** Assets as tiles, without selection. */
export const Default: Story = {render};

/** Click, shift-click or use the keyboard to select tiles, and drag to sort them. */
export const SelectableAndSortable: Story = {
  args: {selectable: true, sortable: true},
  render,
};

/** Smaller tiles fit more to a row. */
export const SmallThumbnails: Story = {
  args: {thumbSize: 'sm'},
  render,
};

/** Placeholder tiles hold the grid’s shape while elements load. */
export const Placeholders: Story = {
  args: {selectable: true, placeholders: 8},
  render,
};
