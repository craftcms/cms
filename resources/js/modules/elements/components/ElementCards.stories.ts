import type {Meta, StoryObj} from '@storybook/vue3-vite';
import ElementCards from './ElementCards.vue';
import {useSelectable} from '@/common/composables/useSelectable';
import {sampleCards} from '@/modules/elements/fixtures/elements';

const meta = {
  title: 'Elements/ElementCards',
  component: ElementCards,
  parameters: {
    docs: {
      description: {
        component:
          'Draws elements as cards, in a grid or one per row. This is the ' +
          'cards view of the element index and of relation fields. Each card ' +
          'renders the HTML the server made for it (`cardHeaderHtml`, ' +
          '`cardContentHtml`, `cardFooterHtml` and the rest) inside a ' +
          '`craft-card`.\n\n' +
          'The cards don’t keep their own selection. Create one with ' +
          '`useSelectable` and pass it as `selection`. Keyboard navigation ' +
          'works in both directions of the grid: Arrows move focus, ' +
          'Space/Enter toggles, Shift+Arrow extends the selection.\n\n' +
          'Plugins draw cards through `ElementList` with a `cards` or ' +
          '`cards-grid` view mode.',
      },
    },
  },
} satisfies Meta<typeof ElementCards>;

export default meta;
interface ElementCardsStoryArgs {
  selectable?: boolean;
  loading?: boolean;
}

type Story = StoryObj<ElementCardsStoryArgs>;

function render(extraProps: Record<string, boolean> = {}) {
  return (args: NonNullable<Story['args']>) => ({
    components: {ElementCards},
    setup() {
      // No table needed: the body takes selection directly now.
      const selection = useSelectable({
        ids: sampleCards.map((card) => card.id),
      });
      return {args: {...args, ...extraProps}, selection, cards: sampleCards};
    },
    template:
      '<ElementCards v-bind="args" :selection="selection" :data="cards" />',
  });
}

export const Default: Story = {
  render: render(),
};

export const Selectable: Story = {
  render: render({selectable: true}),
};

export const Loading: Story = {
  render: render({loading: true}),
};

export const Empty: Story = {
  render: (args) => ({
    components: {ElementCards},
    setup() {
      const selection = useSelectable<number>({ids: []});
      return {args, selection};
    },
    template:
      '<ElementCards v-bind="args" :selection="selection" :data="[]" />',
  }),
};
