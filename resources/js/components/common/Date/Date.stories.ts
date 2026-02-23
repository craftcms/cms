import type {Meta, StoryObj} from '@storybook/vue3-vite';

import DateComponent from './Date.vue';

const meta = {
  title: 'Components/Date',
  component: DateComponent,
  tags: ['autodocs'],
} satisfies Meta<typeof DateComponent>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    value: '2026-02-21T05:00:00.000Z',
  },
};

export const PastDate: Story = {
  args: {
    value: '2024-01-15T14:30:00.000Z',
  },
};
