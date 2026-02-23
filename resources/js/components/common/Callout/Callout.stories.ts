import type {Meta, StoryObj} from '@storybook/vue3-vite';

import Callout from './Callout.vue';

const meta = {
  title: 'Components/Callout',
  component: Callout,
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: ['info', 'success', 'warning', 'danger'],
    },
    appearance: {
      control: 'select',
      options: ['default', 'emphasis', 'outline', 'plain'],
    },
  },
} satisfies Meta<typeof Callout>;

export default meta;
type Story = StoryObj<typeof meta>;

const variants = ['info', 'success', 'warning', 'danger'] as const;
const appearances = ['default', 'emphasis', 'outline', 'plain'] as const;

export const AllVariants: Story = {
  render: () => ({
    components: {Callout},
    setup() {
      return {variants, appearances};
    },
    template: `
      <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
        <template v-for="appearance in appearances" :key="appearance">
          <Callout
            v-for="variant in variants"
            :key="variant + '-' + appearance"
            :variant="variant"
            :appearance="appearance"
          >
            {{ variant }} / {{ appearance }}
          </Callout>
        </template>
      </div>
    `,
  }),
};
