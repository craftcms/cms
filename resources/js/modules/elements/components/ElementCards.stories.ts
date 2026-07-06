import type {Meta, StoryObj} from '@storybook/vue3-vite';
import type {Table} from '@tanstack/vue-table';
import ElementCards from './ElementCards.vue';
import {
  createSampleTable,
  sampleCards,
  sampleEntries,
} from '@/modules/elements/fixtures/elements';

const meta = {
  title: 'Elements/ElementCards',
  component: ElementCards,
  parameters: {
    docs: {
      description: {
        component:
          'The cards view body of an element index. Each card renders ' +
          'server-provided HTML (`cardHeaderHtml`, `cardContentHtml`, ' +
          '`cardFooterHtml`) inside a `craft-card`, while selection state ' +
          'lives on the shared TanStack table. Keyboard navigation works in ' +
          'both directions of the grid: Arrows move focus, Space/Enter ' +
          'toggles, Shift+Arrow extends the selection.',
      },
    },
  },
} satisfies Meta<typeof ElementCards>;

export default meta;
type Story = StoryObj<typeof meta>;

// Each story builds its real table inside setup(); this satisfies the
// required `table` prop in `args`, which the template's :table overrides.
const tablePlaceholder = null as unknown as Table<any>;

function render(extraProps: Record<string, unknown> = {}) {
  return (args: Record<string, unknown>) => ({
    components: {ElementCards},
    setup() {
      const table = createSampleTable({
        data: sampleEntries.slice(0, sampleCards.length),
      });
      return {args: {...args, ...extraProps}, table, cards: sampleCards};
    },
    template: '<ElementCards v-bind="args" :table="table" :data="cards" />',
  });
}

export const Default: Story = {
  render: render(),
  args: {table: tablePlaceholder},
};

export const Selectable: Story = {
  render: render({selectable: true}),
  args: {table: tablePlaceholder},
};

export const Loading: Story = {
  render: render({loading: true}),
  args: {table: tablePlaceholder},
};

export const Empty: Story = {
  render: (args) => ({
    components: {ElementCards},
    setup() {
      const table = createSampleTable({data: []});
      return {args, table};
    },
    template: '<ElementCards v-bind="args" :table="table" :data="[]" />',
  }),
  args: {table: tablePlaceholder},
};
