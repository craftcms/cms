import type {Meta, StoryObj} from '@storybook/vue3';
import {ref} from 'vue';
import {expect, userEvent, waitFor, within} from 'storybook/test';
import Modal from './Modal.vue';

const meta: Meta<typeof Modal> = {
  title: 'Components/Modal',
  component: Modal,
  argTypes: {
    isActive: {control: 'boolean'},
    overlay: {control: 'boolean'},
    width: {control: 'text'},
  },
  args: {
    isActive: false,
    overlay: true,
  },
};

export default meta;
type Story = StoryObj<typeof Modal>;

export const Default: Story = {
  render: (args) => ({
    components: {Modal},
    setup() {
      const isOpen = ref(false);
      return {args, isOpen};
    },
    template: `
      <div>
        <craft-button data-testid="trigger" type="button" @click="isOpen = true">Open Modal</craft-button>

        <Modal v-bind="args" :isActive="isOpen" @close="isOpen = false">
          <div style="background: var(--c-surface-overlay, #fff); padding: 2rem;">
            <h2 style="margin: 0 0 1rem;">Modal Title</h2>
            <p>This is the modal content.</p>
            <craft-button data-testid="close-btn" type="button" @click="isOpen = false">Close</craft-button>
          </div>
        </Modal>
      </div>
    `,
  }),
  play: async ({canvasElement}) => {
    const canvas = within(canvasElement);
    const body = within(document.body);
    const user = userEvent.setup();

    // Modal content should not be visible initially
    await expect(body.queryByTestId('modal-content')).toBeNull();

    // Open the modal
    await user.click(canvas.getByTestId('trigger'));

    // Modal content should be visible
    await waitFor(() => {
      expect(body.getByTestId('modal-content')).not.toBeNull();
    });

    // Close via the button
    await user.click(body.getByTestId('close-btn'));

    await waitFor(() => {
      expect(body.queryByTestId('modal-content')).toBeNull();
    });
  },
};

export const WithoutOverlay: Story = {
  render: () => ({
    components: {Modal},
    setup() {
      const isOpen = ref(false);
      return {isOpen};
    },
    template: `
      <div>
        <craft-button type="button" @click="isOpen = true">Open Modal (No Overlay)</craft-button>

        <Modal :isActive="isOpen" :overlay="false" @close="isOpen = false">
          <div style="background: var(--c-surface-overlay, #fff); padding: 2rem;">
            <h2 style="margin: 0 0 1rem;">No Overlay</h2>
            <p>This modal has no background shade.</p>
            <craft-button type="button" @click="isOpen = false">Close</craft-button>
          </div>
        </Modal>
      </div>
    `,
  }),
};

export const CloseOnEscape: Story = {
  render: () => ({
    components: {Modal},
    setup() {
      const isOpen = ref(false);
      return {isOpen};
    },
    template: `
      <div>
        <craft-button data-testid="trigger" type="button" @click="isOpen = true">Open Modal</craft-button>

        <Modal :isActive="isOpen" @close="isOpen = false">
          <div style="background: var(--c-surface-overlay, #fff); padding: 2rem;">
            <h2 style="margin: 0 0 1rem;">Press Escape to Close</h2>
            <p>This modal closes when you press the Escape key.</p>
          </div>
        </Modal>
      </div>
    `,
  }),
  play: async ({canvasElement}) => {
    const canvas = within(canvasElement);
    const body = within(document.body);
    const user = userEvent.setup();

    // Open the modal
    await user.click(canvas.getByTestId('trigger'));
    await waitFor(() => {
      expect(body.getByTestId('modal-content')).not.toBeNull();
    });

    // Close via Escape
    await user.keyboard('{Escape}');
    await waitFor(() => {
      expect(body.queryByTestId('modal-content')).toBeNull();
    });
  },
};

export const CloseOnShadeClick: Story = {
  render: () => ({
    components: {Modal},
    setup() {
      const isOpen = ref(false);
      return {isOpen};
    },
    template: `
      <div>
        <craft-button data-testid="trigger" type="button" @click="isOpen = true">Open Modal</craft-button>

        <Modal :isActive="isOpen" @close="isOpen = false">
          <div style="background: var(--c-surface-overlay, #fff); padding: 2rem;">
            <h2 style="margin: 0 0 1rem;">Click Shade to Close</h2>
            <p>Click the dark overlay behind this modal to close it.</p>
          </div>
        </Modal>
      </div>
    `,
  }),
  play: async ({canvasElement}) => {
    const canvas = within(canvasElement);
    const body = within(document.body);
    const user = userEvent.setup();

    // Open the modal
    await user.click(canvas.getByTestId('trigger'));
    await waitFor(() => {
      expect(body.getByTestId('modal-content')).not.toBeNull();
    });

    // Click the shade to close
    const shade = document.querySelector<HTMLElement>('.shade')!;
    await user.click(shade);
    await waitFor(() => {
      expect(body.queryByTestId('modal-content')).toBeNull();
    });
  },
};
