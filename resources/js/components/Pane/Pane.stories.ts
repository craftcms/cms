import type {Meta, StoryObj} from '@storybook/vue3';
import Pane from './Pane.vue';

const meta: Meta<typeof Pane> = {
  title: 'Components/Pane',
  component: Pane,
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: [undefined, 'plain', 'code'],
    },
    appearance: {
      control: 'select',
      options: [undefined, 'plain', 'outline', 'raised', 'slideout'],
    },
    padding: {
      control: 'select',
      options: ['sm', 'md', 'lg', 'xl', 0],
    },
    as: {
      control: 'select',
      options: ['div', 'section', 'article', 'aside'],
    },
  },
  args: {
    padding: 'lg',
    as: 'div',
  },
};

export default meta;
type Story = StoryObj<typeof Pane>;

export const Default: Story = {
  render: (args) => ({
    components: {Pane},
    setup() {
      return {args};
    },
    template: `
      <Pane v-bind="args">
        <p>This is the default pane with basic content.</p>
        <p>It uses the default slot for body content.</p>
      </Pane>
    `,
  }),
};

export const WithTitle: Story = {
  render: (args) => ({
    components: {Pane},
    setup() {
      return {args};
    },
    template: `
      <Pane v-bind="args" title="Pane Title">
        <p>This pane has a title prop which creates a header automatically.</p>
      </Pane>
    `,
  }),
};

export const WithHeaderActions: Story = {
  render: () => ({
    components: {Pane},
    template: `
      <Pane title="Settings">
        <template #header-actions>
          <craft-button type="button">Edit</craft-button>
          <craft-button type="button">Delete</craft-button>
        </template>
        <p>This pane has header actions alongside the title.</p>
      </Pane>
    `,
  }),
};

export const WithFooter: Story = {
  render: () => ({
    components: {Pane},
    template: `
      <Pane title="Form Pane">
        <p>This pane has a footer with action buttons.</p>
        <p>The footer sticks to the bottom.</p>

        <template #primary-action>
          <craft-button type="button">Save</craft-button>
        </template>
        <template #secondary-action>
          <craft-button type="button">Cancel</craft-button>
        </template>
      </Pane>
    `,
  }),
};

export const OutlineAppearance: Story = {
  render: () => ({
    components: {Pane},
    template: `
      <Pane appearance="outline" title="Outline Pane">
        <p>This pane has an outline appearance with a visible border.</p>
      </Pane>
    `,
  }),
};

export const RaisedAppearance: Story = {
  render: () => ({
    components: {Pane},
    template: `
      <Pane appearance="raised" title="Raised Pane">
        <p>This pane has a raised appearance with a shadow.</p>
      </Pane>
    `,
  }),
};

export const SlideoutAppearance: Story = {
  render: () => ({
    components: {Pane},
    template: `
      <div style="height: 400px; display: flex;">
        <Pane appearance="slideout" title="Slideout Pane" style="width: 400px;">
          <p>This pane is styled for use within a slideout.</p>
          <p>It has a flex column layout with distinct header/footer styling.</p>

          <template #primary-action>
            <craft-button type="button">Save</craft-button>
          </template>
          <template #secondary-action>
            <craft-button type="button">Cancel</craft-button>
          </template>
        </Pane>
      </div>
    `,
  }),
};
export const CodeVariant: Story = {
  render: () => ({
    components: {Pane},
    template: `
      <Pane variant="code">
        <pre><code>function hello() {
  console.log('Hello, World!');
}

hello();</code></pre>
      </Pane>
    `,
  }),
};

export const CustomPadding: Story = {
  render: () => ({
    components: {Pane},
    template: `
      <div style="display: flex; flex-direction: column; gap: 1rem;">
        <Pane appearance="outline" padding="sm" title="Small Padding">
          <p>padding="sm"</p>
        </Pane>

        <Pane appearance="outline" padding="md" title="Medium Padding">
          <p>padding="md"</p>
        </Pane>

        <Pane appearance="outline" padding="lg" title="Large Padding">
          <p>padding="lg"</p>
        </Pane>

        <Pane appearance="outline" padding="xl" title="Extra Large Padding">
          <p>padding="xl"</p>
        </Pane>

        <Pane appearance="outline" :padding="0" title="No Padding">
          <p>padding={0}</p>
        </Pane>
      </div>
    `,
  }),
};

export const CustomHeaderSlot: Story = {
  render: () => ({
    components: {Pane},
    template: `
      <Pane appearance="outline">
        <template #header>
          <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f0f0f0;">
            <h2 style="margin: 0;">Custom Header</h2>
            <span>Badge</span>
          </div>
        </template>
        <p>This pane uses a completely custom header slot.</p>
      </Pane>
    `,
  }),
};

export const AsForm: Story = {
  render: () => ({
    components: {Pane},
    template: `
      <Pane as="form" appearance="outline" title="Form Element" @submit.prevent="() => alert('Submitted!')">
        <p>This pane renders as a form element.</p>
        <input type="text" placeholder="Enter something..." style="width: 100%; padding: 0.5rem;" />

        <template #primary-action>
          <craft-button type="submit">Submit</craft-button>
        </template>
      </Pane>
    `,
  }),
};

export const FullExample: Story = {
  render: () => ({
    components: {Pane},
    template: `
      <Pane appearance="raised" title="User Settings">
        <template #header-actions>
          <craft-button type="button">Reset</craft-button>
        </template>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
          <div>
            <label style="display: block; margin-bottom: 0.25rem;">Username</label>
            <input type="text" value="johndoe" style="width: 100%; padding: 0.5rem;" />
          </div>
          <div>
            <label style="display: block; margin-bottom: 0.25rem;">Email</label>
            <input type="email" value="john@example.com" style="width: 100%; padding: 0.5rem;" />
          </div>
          <div>
            <label style="display: block; margin-bottom: 0.25rem;">Bio</label>
            <textarea rows="3" style="width: 100%; padding: 0.5rem;">Software developer</textarea>
          </div>
        </div>

        <template #secondary-action>
          <craft-button type="button">Cancel</craft-button>
        </template>
        <template #primary-action>
          <craft-button type="button">Save Changes</craft-button>
        </template>
      </Pane>
    `,
  }),
};
