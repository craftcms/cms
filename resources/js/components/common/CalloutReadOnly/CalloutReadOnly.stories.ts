import type {Meta, StoryObj} from '@storybook/vue3-vite';

import CalloutReadOnly from './CalloutReadOnly.vue';

const meta = {
  title: 'Components/CalloutReadOnly',
  component: CalloutReadOnly,
  tags: ['autodocs'],
} satisfies Meta<typeof CalloutReadOnly>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const CustomMessage: Story = {
  render: () => ({
    components: {CalloutReadOnly},
    template: `<CalloutReadOnly>This setting is locked in production.</CalloutReadOnly>`,
  }),
};
