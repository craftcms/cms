import type {Meta, StoryObj} from '@storybook/vue3-vite';

import DynamicHtmlRenderer from './DynamicHtmlRenderer.vue';

const meta = {
  title: 'Components/DynamicHtmlRenderer',
  component: DynamicHtmlRenderer,
  tags: ['autodocs'],
} satisfies Meta<typeof DynamicHtmlRenderer>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    html: '<div><h2>Hello World</h2><p>This HTML was rendered dynamically.</p></div>',
  },
};

export const WithVueBindings: Story = {
  args: {
    html: '<ul><li>Item 1</li><li>Item 2</li><li>Item 3</li></ul>',
  },
};
