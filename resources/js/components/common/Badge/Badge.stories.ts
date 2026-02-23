import type {Meta, StoryObj} from '@storybook/vue3-vite';

import Badge from './Badge.vue';

const meta = {
  title: 'Components/Badge',
  component: Badge,
  tags: ['autodocs'],
} satisfies Meta<typeof Badge>;

export default meta;
type Story = StoryObj<typeof meta>;

export const AllVariants: Story = {
  render: () => ({
    components: {Badge},
    template: `
      <div style="display: flex; gap: 1rem; align-items: center;">
        <Badge variant="default">Default</Badge>
        <Badge variant="success">Success</Badge>
        <Badge variant="danger">Danger</Badge>
        <Badge variant="warning">Warning</Badge>
      </div>
    `,
  }),
};
