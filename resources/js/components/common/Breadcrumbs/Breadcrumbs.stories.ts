import type {Meta, StoryObj} from '@storybook/vue3-vite';

import Breadcrumbs from './Breadcrumbs.vue';

const meta = {
  title: 'Components/Breadcrumbs',
  component: Breadcrumbs,
  tags: ['autodocs'],
} satisfies Meta<typeof Breadcrumbs>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    items: [
      {url: '#', label: 'Home'},
      {url: '#', label: 'Settings'},
      {label: 'General'},
    ],
  },
};

export const SingleItem: Story = {
  args: {
    items: [{label: 'Dashboard'}],
  },
};

export const CustomSeparator: Story = {
  args: {
    items: [
      {url: '#', label: 'Home'},
      {url: '#', label: 'Settings'},
      {label: 'General'},
    ],
    separator: '›',
  },
};
