import type {Meta, StoryObj} from '@storybook/vue3-vite';

import ReorderButton from './ReorderButton.vue';

const meta = {
  title: 'Components/ReorderButton',
  component: ReorderButton,
  tags: ['autodocs'],
} satisfies Meta<typeof ReorderButton>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const CustomLabel: Story = {
  args: {
    label: 'Drag to reorder',
  },
};
