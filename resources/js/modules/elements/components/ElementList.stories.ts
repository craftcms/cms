import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {ref} from 'vue';
import ElementList from './ElementList.vue';
import {
  type ElementListViewMode,
  useElementList,
} from '@/modules/elements/composables/useElementList';
import {
  sampleAssets,
  sampleElements,
} from '@/modules/elements/fixtures/elements';

/**
 * Entries with the parts every view mode draws from. The server only renders
 * the parts the active mode needs; the story has them all so the mode can be
 * switched.
 */
const sampleListElements = sampleElements.map((element, index) => ({
  ...element,
  thumbHtml: sampleAssets[index]?.thumbHtml,
  cardHeaderHtml: `<div class="card-titlebar-label">${element.label}</div>`,
  cardContentHtml: `<p>${element.status?.label ?? ''} · Updated ${index + 1} days ago</p>`,
  cardFooterHtml: '',
}));

interface ElementListStoryArgs {
  viewMode: ElementListViewMode;
  thumbSize?: 'sm' | 'md' | 'lg';
  selectable?: boolean;
  sortable?: boolean;
  readOnly?: boolean;
  loading?: boolean;
}

const meta = {
  title: 'Elements/ElementList',
  component: ElementList,
  args: {
    viewMode: 'list',
    selectable: false,
    sortable: false,
    readOnly: false,
    loading: false,
  },
  argTypes: {
    viewMode: {
      control: 'inline-radio',
      options: ['list', 'list-inline', 'thumbs', 'cards', 'cards-grid'],
    },
    thumbSize: {
      control: 'inline-radio',
      options: [undefined, 'sm', 'md', 'lg'],
    },
  },
  parameters: {
    docs: {
      description: {
        component:
          'Draws a list of elements in any of the view modes a relation ' +
          'field offers: chips in `list` and `list-inline`, thumbnail tiles ' +
          'in `thumbs`, and cards in `cards` and `cards-grid`. It renders ' +
          '`ElementChips`, `ElementThumbs` or `ElementCards` for the mode, ' +
          'with the same events and slots.\n\n' +
          'Pass the elements as `data`, in display order. A PHP controller ' +
          'can build each one with `app(ElementHtml::class)->presentation(' +
          '$element, $viewMode)`, which includes the parts that view mode needs.\n\n' +
          'Create the list’s state with `useElementList`. It gives you a ' +
          'selection to pass as `selection`, when the page needs to know ' +
          'what’s selected, and flags for the active mode. ' +
          'The list emits `reorder` with the old and new index when it’s ' +
          '`sortable`, and `edit` with the element on a double-click. The ' +
          '`actions` slot adds controls to each chip, tile or card.\n\n' +
          "```ts\nimport {ElementList, useElementList} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof ElementList>;

export default meta;
type Story = StoryObj<ElementListStoryArgs>;

function render(args: NonNullable<Story['args']>) {
  return {
    components: {ElementList},
    setup() {
      const items = ref([...sampleListElements]);
      const list = useElementList({
        ids: () => items.value.map((element) => element.id),
        viewMode: () => args.viewMode ?? 'list',
        selectable: () => args.selectable ?? false,
        readOnly: () => args.readOnly ?? false,
      });

      function reorder(from: number, to: number) {
        const [moved] = items.value.splice(from, 1);
        if (moved) {
          items.value.splice(to, 0, moved);
        }
      }

      function remove(id: number) {
        items.value = items.value.filter((element) => element.id !== id);
      }

      return {args, items, list, reorder, remove};
    },
    template: `
      <ElementList
        v-bind="args"
        :data="items"
        :selection="list.selection"
        @reorder="reorder"
      >
        <template #actions="{element}">
          <craft-button
            v-if="element"
            type="button"
            size="small"
            variant="plain"
            icon
            @click="remove(element.id)"
          >
            <craft-icon name="xmark" label="Remove"></craft-icon>
          </craft-button>
        </template>
      </ElementList>
      <p v-if="args.selectable" style="margin-top: 1rem">
        {{ list.selection.selectedIds.value.length }} selected
      </p>
    `,
  };
}

/** Chips, the way a relation field shows its elements by default. */
export const List: Story = {render};

/** Thumbnail tiles, with a button on each to remove it. */
export const Thumbs: Story = {
  args: {viewMode: 'thumbs'},
  render,
};

/** Cards in a grid. */
export const CardsGrid: Story = {
  args: {viewMode: 'cards-grid'},
  render,
};

/** A list that can be selected and sorted, as an editable relation field is. */
export const SelectableAndSortable: Story = {
  args: {selectable: true, sortable: true},
  render,
};
