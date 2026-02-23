import type {Meta, StoryObj} from '@storybook/vue3-vite';

import Text from './Text.vue';

const meta = {
  title: 'Components/Text',
  component: Text,
  tags: ['autodocs'],
} satisfies Meta<typeof Text>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    template: '{from} – {to} of {total, plural, =1{# item} other{# items}}',
    params: {from: 1, to: 25, total: 100},
  },
};

export const AsSpan: Story = {
  args: {
    as: 'span',
    template: 'Hello, {name}!',
    params: {name: 'World'},
  },
};
