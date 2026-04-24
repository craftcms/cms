import type {
  ComponentPropsAndSlots,
  Meta,
  StoryObj,
} from '@storybook/vue3-vite';
import {createPlugin} from '@/fixtures/plugins';

import PluginDetails from './PluginDetails.vue';

const meta = {
  component: PluginDetails,
} satisfies Meta<typeof PluginDetails>;

export default meta;
type Story = StoryObj<typeof meta>;

function render(args: ComponentPropsAndSlots<typeof PluginDetails>) {
  return {
    components: {PluginDetails},
    setup() {
      return {args};
    },
    template: '<PluginDetails v-bind="args"/>',
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
    plugin: createPlugin(),
  },
};

export const ValidLicense: Story = {
  render,
  args: {
    plugin: createPlugin({
      licenseKeyStatus: 'valid',
      licenseKey: 'ABCDEFGHIJKLMNOP',
      licensedEdition: 'pro',
    }),
  },
};

export const TrialLicense: Story = {
  render,
  args: {
    plugin: createPlugin({
      isTrial: true,
      licenseKeyStatus: 'trial',
      licenseKey: 'ABCDEFGHIJKLMNOP',
      licenseIssues: ['no_trials'],
      licensedEdition: 'pro',
    }),
  },
};

export const InvalidLicense: Story = {
  render,
  args: {
    plugin: createPlugin({
      licenseKeyStatus: 'invalid',
      licenseKey: 'ABCDEFGHIJKLMNOP',
      licenseIssues: ['invalid'],
      licensedEdition: 'lite',
    }),
  },
};

export const AllLicenseIssues: Story = {
  render,
  args: {
    plugin: createPlugin({
      licenseKey: 'ABCDEFGHIJKLMNOP',
      licenseKeyStatus: 'trial',
      licensedEdition: 'lite',
      licenseIssues: [
        'wrong_edition',
        'no_trials',
        'mismatched',
        'astray',
        'required',
        'any',
      ],
    }),
  },
};
