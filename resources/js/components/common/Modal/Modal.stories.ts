import type {Meta, StoryObj} from '@storybook/vue3-vite';

import Modal from './Modal.vue';

const meta = {
  title: 'Components/Modal',
  component: Modal,
  tags: ['autodocs'],
  parameters: {
    layout: 'fullscreen',
  },
} satisfies Meta<typeof Modal>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    isActive: true,
    overlay: true,
  },
  render: (args) => ({
    components: {Modal},
    setup() {
      return {args};
    },
    template: `
      <Modal v-bind="args">
        <div style="background: white; padding: 2rem; border-radius: 0.5rem;">
          <h2>Modal Content</h2>
          <p>This is the content inside the modal.</p>
        </div>
      </Modal>
    `,
  }),
};

export const WithoutOverlay: Story = {
  args: {
    isActive: true,
    overlay: false,
  },
  render: (args) => ({
    components: {Modal},
    setup() {
      return {args};
    },
    template: `
      <Modal v-bind="args">
        <div style="background: white; padding: 2rem; border-radius: 0.5rem; border: 1px solid #ccc;">
          <h2>No Overlay</h2>
          <p>This modal has no background overlay.</p>
        </div>
      </Modal>
    `,
  }),
};

export const Inactive: Story = {
  args: {
    isActive: false,
    overlay: true,
  },
  render: (args) => ({
    components: {Modal},
    setup() {
      return {args};
    },
    template: `
      <div>
        <p>The modal is currently hidden (isActive: false).</p>
        <Modal v-bind="args">
          <div style="background: white; padding: 2rem;">
            <p>You should not see this.</p>
          </div>
        </Modal>
      </div>
    `,
  }),
};
