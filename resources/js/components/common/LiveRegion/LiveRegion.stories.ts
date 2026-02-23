import type {Meta, StoryObj} from '@storybook/vue3-vite';

import LiveRegion from './LiveRegion.vue';

const meta = {
  title: 'Components/LiveRegion',
  component: LiveRegion,
  tags: ['autodocs'],
  parameters: {
    docs: {
      description: {
        component:
          'An accessible live region that announces messages to screen readers. The content is visually hidden (sr-only) but read aloud by assistive technology.',
      },
    },
  },
} satisfies Meta<typeof LiveRegion>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};
