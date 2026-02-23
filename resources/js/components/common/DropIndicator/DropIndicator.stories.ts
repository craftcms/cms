import type {Meta, StoryObj} from '@storybook/vue3-vite';

import DropIndicator from './DropIndicator.vue';

const meta = {
  title: 'Components/DropIndicator',
  component: DropIndicator,
  tags: ['autodocs'],
  decorators: [
    () => ({
      template:
        '<div style="position: relative; height: 60px; border: 1px dashed #ccc; margin: 2rem;"><story /></div>',
    }),
  ],
} satisfies Meta<typeof DropIndicator>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Top: Story = {
  args: {
    edge: 'top',
  },
};

export const Bottom: Story = {
  args: {
    edge: 'bottom',
  },
};

export const Hidden: Story = {
  args: {
    edge: null,
  },
};
