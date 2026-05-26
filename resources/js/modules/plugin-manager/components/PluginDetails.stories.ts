import type {
  ComponentPropsAndSlots,
  Meta,
  StoryObj,
} from '@storybook/vue3-vite';
import {createPlugin} from '@/modules/plugin-manager/fixtures/plugins';

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

export const Expired: Story = {
  render,
  args: {
    plugin: createPlugin({
      name: 'Formie',
      handle: 'formie',
      description: 'The most user-friendly forms plugin for Craft.',
      iconUrl: 'https://pluginicons.craft-cdn.com/formie.svg',
      documentationUrl: 'https://verbb.io/craft-plugins/formie',
      packageName: 'verbb/formie',
      version: '3.1.21',
      licenseKey: 'XXXXXXXXXXXXXXXXXXXXXXXX',
      licensedEdition: 'standard',
      licenseKeyStatus: 'valid',
      expired: true,
      renewalUrl: '#',
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
