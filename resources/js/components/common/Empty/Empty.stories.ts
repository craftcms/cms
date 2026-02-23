import type {Meta, StoryObj} from '@storybook/vue3-vite';

import Empty from './Empty.vue';

const meta = {
  title: 'Components/Empty',
  component: Empty,
  tags: ['autodocs'],
  argTypes: {
    icon: {control: 'text'},
    label: {control: 'text'},
  },
} satisfies Meta<typeof Empty>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    icon: 'magnifying-glass',
    label: 'No results found',
  },
};

export const WithLabel: Story = {
  args: {
    label: 'Nothing here yet',
  },
};

export const WithIcon: Story = {
  args: {
    icon: 'magnifying-glass',
  },
};
