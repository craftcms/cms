import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {ref} from 'vue';
import ModalForm from './ModalForm.vue';

const widths = [
  'sm',
  'md',
  'lg',
  'xl',
  '2xl',
  '3xl',
  '4xl',
  '5xl',
  '6xl',
  '7xl',
];

const meta = {
  title: 'CP/ModalForm',
  component: ModalForm,
  args: {
    title: 'Rename group',
    width: 'sm',
    submitLabel: 'Save',
    resetLabel: 'Cancel',
    loading: false,
    submitDisabled: false,
    dismissible: true,
    overlay: true,
    resizable: false,
  },
  argTypes: {
    isActive: {control: false},
    width: {control: 'select', options: widths},
  },
  parameters: {
    docs: {
      description: {
        component:
          'A modal with a title, a close button, a form body and Cancel/Save ' +
          'buttons in its footer. Put your fields in the default slot. ' +
          'Submitting the form, by the Save button or by pressing Enter, ' +
          'emits `submit`. The Cancel and close buttons emit `close`.\n\n' +
          'Set `loading` while the request runs to show a spinner on the Save ' +
          'button. The `primary-action`, `secondary-action` and `feedback` ' +
          'slots replace or add to the footer.\n\n' +
          "```ts\nimport {ModalForm} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof ModalForm>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Clicking Save shows the spinner for a moment, then closes the modal. */
export const Default: Story = {
  render: (args) => ({
    components: {ModalForm},
    setup() {
      const open = ref(false);
      const saving = ref(false);
      const name = ref('Blog');
      function save() {
        saving.value = true;
        setTimeout(() => {
          saving.value = false;
          open.value = false;
        }, 800);
      }
      return {args, open, saving, name, save};
    },
    template: `
      <craft-button type="button" @click="open = true">Rename group</craft-button>
      <ModalForm
        v-bind="args"
        :is-active="open"
        :loading="saving"
        @close="open = false"
        @submit="save"
      >
        <craft-input
          label="Name"
          .value="name"
          @input="name = $event.target.value"
          required
        ></craft-input>
      </ModalForm>
    `,
  }),
};

/**
 * `submit-disabled` greys out Save until there's something to submit. Pressing
 * Enter still emits `submit`, so the handler checks too.
 */
export const SubmitDisabled: Story = {
  args: {
    title: 'Delete site',
    submitLabel: 'Delete',
  },
  render: (args) => ({
    components: {ModalForm},
    setup() {
      const open = ref(false);
      const confirmation = ref('');
      function submit() {
        if (confirmation.value !== 'French') return;
        open.value = false;
      }
      return {args, open, confirmation, submit};
    },
    template: `
      <craft-button type="button" variant="danger" @click="open = true">Delete site…</craft-button>
      <ModalForm
        v-bind="args"
        :is-active="open"
        :submit-disabled="confirmation !== 'French'"
        @close="open = false"
        @submit="submit"
      >
        <p class="mb-3">
          Entries that only exist in the French site will be deleted.
          Type <strong>French</strong> to confirm.
        </p>
        <craft-input
          label="Site name"
          .value="confirmation"
          @input="confirmation = $event.target.value"
        ></craft-input>
      </ModalForm>
    `,
  }),
};

/** The `feedback` slot puts a note at the start of the footer. */
export const WithFeedback: Story = {
  args: {
    title: 'Create a token',
    width: 'md',
    submitLabel: 'Create token',
  },
  render: (args) => ({
    components: {ModalForm},
    setup() {
      const open = ref(false);
      const name = ref('');
      return {args, open, name};
    },
    template: `
      <craft-button type="button" @click="open = true">New token</craft-button>
      <ModalForm
        v-bind="args"
        :is-active="open"
        @close="open = false"
        @submit="open = false"
      >
        <craft-input
          label="Name"
          help-text="What will use this token, such as “Website build”."
          .value="name"
          @input="name = $event.target.value"
        ></craft-input>
        <template #feedback>
          <span class="text-sm">The token is shown once, right after it's created.</span>
        </template>
      </ModalForm>
    `,
  }),
};
