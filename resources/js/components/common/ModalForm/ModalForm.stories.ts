import type {Meta, StoryObj} from '@storybook/vue3-vite';

import ModalForm from './ModalForm.vue';

const meta = {
  title: 'Components/ModalForm',
  component: ModalForm,
  tags: ['autodocs'],
  parameters: {
    layout: 'fullscreen',
  },
} satisfies Meta<typeof ModalForm>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    isActive: true,
    title: 'Edit Item',
  },
  render: (args) => ({
    components: {ModalForm},
    setup() {
      return {args};
    },
    template: `
      <ModalForm v-bind="args" @close="args.isActive = false">
        <p>Form content goes here.</p>
      </ModalForm>
    `,
  }),
};

export const Loading: Story = {
  args: {
    isActive: true,
    title: 'Saving...',
    loading: true,
  },
  render: (args) => ({
    components: {ModalForm},
    setup() {
      return {args};
    },
    template: `
      <ModalForm v-bind="args">
        <p>This form is currently submitting.</p>
      </ModalForm>
    `,
  }),
};

export const CustomLabels: Story = {
  args: {
    isActive: true,
    title: 'Delete Item',
    submitLabel: 'Delete',
    resetLabel: 'Nevermind',
  },
  render: (args) => ({
    components: {ModalForm},
    setup() {
      return {args};
    },
    template: `
      <ModalForm v-bind="args">
        <p>Are you sure you want to delete this item?</p>
      </ModalForm>
    `,
  }),
};
