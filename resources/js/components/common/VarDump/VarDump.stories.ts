import type {Meta, StoryObj} from '@storybook/vue3-vite';

import VarDump from './VarDump.vue';

const meta = {
  title: 'Components/VarDump',
  component: VarDump,
  tags: ['autodocs'],
} satisfies Meta<typeof VarDump>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    data: {
      name: 'Craft CMS',
      version: '6.0.0',
      plugins: ['commerce', 'seo'],
      config: {
        devMode: true,
        allowUpdates: false,
      },
    },
  },
};

export const SimpleArray: Story = {
  args: {
    data: ['one', 'two', 'three'],
  },
};

export const Primitive: Story = {
  args: {
    data: 'Hello, World!',
  },
};
