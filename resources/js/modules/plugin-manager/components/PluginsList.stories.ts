import type {
  ComponentPropsAndSlots,
  Meta,
  StoryObj,
} from '@storybook/vue3-vite';
import {
  createPlugin,
  pluginInfo,
} from '@/modules/plugin-manager/fixtures/plugins';
import {nextTick, onMounted, useTemplateRef} from 'vue';

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

function failureStory(messages: string[]): Story {
  return {
    args: {
      pluginInfo: Object.fromEntries(
        messages.map((_, index) => {
          const handle = `example-${index + 1}`;
          return [
            handle,
            createPlugin({
              handle,
              name: `Example Plugin ${index + 1}`,
              isInstalled: false,
              isEnabled: false,
            }),
          ];
        })
      ),
    },
    render(args) {
      return {
        components: {PluginsList},
        setup() {
          const harness = useTemplateRef<HTMLElement>('harness');
          onMounted(async () => {
            await nextTick();
            const table = harness.value!.querySelector('.element-index')!;
            messages.forEach((message) => {
              table.dispatchEvent(
                new CustomEvent('action:change-state', {
                  bubbles: true,
                  composed: true,
                  detail: {state: 'error', actionType: 'http', message},
                })
              );
            });
          });
          return {args, harness};
        },
        template: '<div ref="harness"><PluginsList v-bind="args" /></div>',
      };
    },
  };
}

export const ShortFailure: Story = {...failureStory(['Server Error'])};

export const LargeMultilineFailure: Story = {
  ...failureStory([
    'Migration failed.\n\n<img src="example" onerror="alert(1)">\n' +
      'UnbrokenDiagnostic'.repeat(100) +
      '\n' +
      'Additional diagnostic line.\n'.repeat(200),
  ]),
};

export const MultipleFailures: Story = {
  ...failureStory([
    'A plugin action raised an error.',
    'Migration failed.\nReview the application logs for more information.',
  ]),
};
