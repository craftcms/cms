import type {Meta, StoryObj} from '@storybook/vue3-vite';

import Release from './Release.vue';

const meta = {
  title: 'Utilities/Updates/Release',
  component: Release,
  tags: ['autodocs'],
} satisfies Meta<typeof Release>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    version: '5.6.1',
    date: '2026-02-10T00:00:00Z',
    critical: false,
    notes:
      '<h3>Bug Fixes</h3><ul><li>Fixed an issue with asset indexing.</li><li>Improved performance of element queries.</li></ul>',
  },
};

export const Critical: Story = {
  args: {
    version: '5.6.2',
    date: '2026-02-15T00:00:00Z',
    critical: true,
    notes:
      '<h3>Security</h3><ul><li>Fixed a critical XSS vulnerability in template rendering.</li></ul><blockquote class="warning">This is a critical security update. Please update immediately.</blockquote>',
  },
};

export const NoNotes: Story = {
  args: {
    version: '5.6.0',
    date: '2026-02-01T00:00:00Z',
    critical: false,
    notes: null,
  },
};

export const AllVariants: Story = {
  render: () => ({
    components: {Release},
    template: `
      <div style="display: grid; gap: 0.5rem;">
        <Release
          version="5.6.2"
          date="2026-02-15T00:00:00Z"
          :critical="true"
          notes="<h3>Security</h3><ul><li>Fixed a critical vulnerability.</li></ul>"
        />
        <Release
          version="5.6.1"
          date="2026-02-10T00:00:00Z"
          :critical="false"
          notes="<h3>Bug Fixes</h3><ul><li>Minor fixes.</li></ul>"
        />
        <Release
          version="5.6.0"
          date="2026-02-01T00:00:00Z"
          :critical="false"
          :notes="null"
        />
      </div>
    `,
  }),
};
