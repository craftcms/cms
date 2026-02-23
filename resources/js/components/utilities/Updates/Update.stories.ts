import type {Meta, StoryObj} from '@storybook/vue3-vite';

import Update from './Update.vue';

const meta = {
  title: 'Utilities/Updates/Update',
  component: Update,
  tags: ['autodocs'],
} satisfies Meta<typeof Update>;

export default meta;
type Story = StoryObj<typeof meta>;

const sampleReleases = [
  {
    version: '5.6.2',
    date: '2026-02-15T00:00:00Z',
    critical: true,
    notes:
      '<h3>Security</h3><ul><li>Fixed a critical XSS vulnerability.</li></ul>',
  },
  {
    version: '5.6.1',
    date: '2026-02-10T00:00:00Z',
    critical: false,
    notes:
      '<h3>Bug Fixes</h3><ul><li>Fixed asset indexing issue.</li><li>Improved query performance.</li></ul>',
  },
  {
    version: '5.6.0',
    date: '2026-02-01T00:00:00Z',
    critical: false,
    notes:
      '<h3>Features</h3><ul><li>Added new field type.</li><li>Improved CP navigation.</li></ul>',
  },
];

export const Eligible: Story = {
  args: {
    name: 'Craft CMS',
    handle: 'craft',
    packageName: 'craftcms/cms',
    status: 'eligible',
    latestVersion: '5.6.2',
    releases: sampleReleases,
    allowUpdates: true,
  },
};

export const ExpiredLicense: Story = {
  args: {
    name: 'Commerce',
    handle: 'commerce',
    packageName: 'craftcms/commerce',
    status: 'expired',
    statusText:
      'Your Commerce license has expired. Renew to continue receiving updates.',
    latestVersion: '5.2.0',
    releases: sampleReleases.slice(0, 1),
    allowUpdates: true,
    ctaText: 'Renew for $59',
    ctaUrl: 'https://plugins.craftcms.com/commerce',
  },
};

export const Abandoned: Story = {
  args: {
    name: 'Old Plugin',
    handle: 'old-plugin',
    packageName: 'vendor/old-plugin',
    status: 'eligible',
    statusText:
      'This plugin has been abandoned. Consider switching to New Plugin.',
    abandoned: true,
    latestVersion: null,
    releases: [],
    allowUpdates: true,
  },
};

export const ReadOnly: Story = {
  args: {
    name: 'Craft CMS',
    handle: 'craft',
    packageName: 'craftcms/cms',
    status: 'eligible',
    latestVersion: '5.6.2',
    releases: sampleReleases,
    allowUpdates: false,
  },
};
