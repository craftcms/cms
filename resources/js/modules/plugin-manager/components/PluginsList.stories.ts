import type {
  ComponentPropsAndSlots,
  Meta,
  StoryObj,
} from '@storybook/vue3-vite';
import {pluginInfo} from '@/modules/plugin-manager/fixtures/plugins';

import PluginsList from './PluginsList.vue';

const meta = {
  component: PluginsList,
} satisfies Meta<typeof PluginsList>;

export default meta;
type Story = StoryObj<typeof meta>;

function render(args: ComponentPropsAndSlots<typeof PluginsList>) {
  return {
    components: {PluginsList},
    setup() {
      return {args};
    },
    template: '<PluginsList v-bind="args"/>',
  };
}

/*
 *👇 Render functions are a framework specific feature to allow you control on how the component renders.
 * See https://storybook.js.org/docs/api/csf
 * to learn how to use render functions.
 */
export const Default: Story = {
  render,
  args: {
    pluginInfo,
  },
};
