import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {computed, ref} from 'vue';
import ElementChips from './ElementChips.vue';
import {useSelectable} from '@/common/composables/useSelectable';
import {
  sampleAssets,
  sampleCategories,
  sampleElements,
  type SampleElement,
} from '@/modules/elements/fixtures/elements';

interface ElementChipsStoryArgs {
  inline?: boolean;
  selectable?: boolean;
  readOnly?: boolean;
  sortable?: boolean;
  linked?: boolean;
  thumbSize?: 'sm' | 'md' | 'lg';
}

const meta = {
  title: 'Elements/ElementChips',
  component: ElementChips,
  args: {
    inline: false,
    selectable: false,
    readOnly: false,
    sortable: false,
    linked: false,
  },
  argTypes: {
    thumbSize: {
      control: 'inline-radio',
      options: [undefined, 'sm', 'md', 'lg'],
    },
  },
  parameters: {
    docs: {
      description: {
        component:
          'Draws elements as chips, stacked or in a wrapping row. This is ' +
          'how relation fields in the list view modes show their elements. ' +
          'Each chip shows the element’s label, and its status, color and ' +
          'thumbnail when the data has them.\n\n' +
          'Pass the elements as `data`, in display order. A PHP controller ' +
          'can build each one with `app(ElementHtml::class)->presentation($element)`. ' +
          'Elements with a `level` deeper than the one before them are ' +
          'nested under it.\n\n' +
          'To read or change what’s selected, create a selection with ' +
          '`useSelectable` and pass it as `selection`. Without one, the chips ' +
          'keep their own. With `sortable`, the chips emit `reorder` with the old and ' +
          'new index, and you move the element in your own data. A ' +
          'double-click emits `edit`. The `prefix`, `suffix` and `append` ' +
          'slots add controls to each chip, such as an action menu or a ' +
          'hidden input.\n\n' +
          "```ts\nimport {ElementChips, useSelectable} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof ElementChips>;

export default meta;
type Story = StoryObj<ElementChipsStoryArgs>;

function render(elements: Array<SampleElement>) {
  return (args: NonNullable<Story['args']>) => ({
    components: {ElementChips},
    setup() {
      const items = ref([...elements]);
      const selection = useSelectable({
        ids: () => items.value.map((element) => element.id),
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
      <div style="max-width: 28rem">
        <ElementChips
          v-bind="args"
          :data="items"
          :selection="selection"
          @reorder="reorder"
        />
        <p v-if="args.selectable" style="margin-top: 1rem">
          {{ selectedCount }} selected
        </p>
      </div>
    `,
  });
}

/** Entries as a stacked list, the way a relation field shows them. */
export const List: Story = {
  render: render(sampleElements),
};

/** A wrapping row of chips with small thumbnails. */
export const InlineWithThumbnails: Story = {
  args: {inline: true, thumbSize: 'sm'},
  render: render(sampleAssets.slice(0, 5)),
};

/** Click or shift-click to select, and drag or use the reorder buttons to sort. */
export const SelectableAndSortable: Story = {
  args: {selectable: true, sortable: true},
  render: render(sampleElements),
};

/** Elements with structure levels nest under the element before them. */
export const Hierarchy: Story = {
  args: {linked: true},
  render: render(sampleCategories),
};
