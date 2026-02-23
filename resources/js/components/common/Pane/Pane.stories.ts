import type {Meta, StoryObj} from '@storybook/vue3-vite';

import Pane from './Pane.vue';

const meta = {
  title: 'Components/Pane',
  component: Pane,
  tags: ['autodocs'],
} satisfies Meta<typeof Pane>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  render: () => ({
    components: {Pane},
    template: `
      <Pane>
        <p>Default pane content.</p>
      </Pane>
    `,
  }),
};

export const WithTitle: Story = {
  args: {
    title: 'Settings',
  },
  render: (args) => ({
    components: {Pane},
    setup() {
      return {args};
    },
    template: `
      <Pane v-bind="args">
        <p>Pane with a title in the header.</p>
      </Pane>
    `,
  }),
};

export const WithActions: Story = {
  args: {
    title: 'Edit Entry',
  },
  render: (args) => ({
    components: {Pane},
    setup() {
      return {args};
    },
    template: `
      <Pane v-bind="args">
        <p>Pane with footer actions.</p>
        <template #secondary-action>
          <craft-button appearance="plain">Cancel</craft-button>
        </template>
        <template #primary-action>
          <craft-button variant="primary">Save</craft-button>
        </template>
      </Pane>
    `,
  }),
};

export const AllAppearances: Story = {
  render: () => ({
    components: {Pane},
    template: `
      <div style="display: grid; gap: 1.5rem;">
        <Pane title="Plain" appearance="plain">
          <p>appearance="plain"</p>
        </Pane>
        <Pane title="Outline" appearance="outline">
          <p>appearance="outline"</p>
        </Pane>
        <Pane title="Raised" appearance="raised">
          <p>appearance="raised"</p>
        </Pane>
      </div>
    `,
  }),
};

export const AllVariants: Story = {
  render: () => ({
    components: {Pane},
    template: `
      <div style="display: grid; gap: 1.5rem;">
        <Pane title="Code" variant="code">
          <pre>const x = 42;</pre>
        </Pane>
        <Pane title="Error" variant="error">
          <p>Something went wrong.</p>
        </Pane>
      </div>
    `,
  }),
};
