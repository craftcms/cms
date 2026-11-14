import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {useForm} from '@inertiajs/vue3';
import FormActions from './FormActions.vue';
import type {ActionItem, ActionItemButton} from '@/common/types';

const meta = {
  title: 'CP/FormActions',
  component: FormActions,
  args: {
    submitLabel: undefined,
    readOnly: false,
    saveDisabled: false,
  },
  argTypes: {
    form: {control: false},
    actionItems: {control: false},
    additionalActions: {control: false},
    additionalButtons: {control: false},
  },
  parameters: {
    docs: {
      description: {
        component:
          'The Save button for an Inertia form, with optional extras beside ' +
          'it: a menu of other ways to save, more buttons, and an overflow ' +
          'menu. Pass the form from `useForm()`. The Save button is a submit ' +
          'button, so put this inside your `<form>`. While the form is ' +
          'processing every button is disabled, and the one that started the ' +
          'request shows a spinner.\n\n' +
          "```ts\nimport {FormActions} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof FormActions>;

export default meta;

interface FormActionsStoryArgs {
  submitLabel?: string;
  readOnly?: boolean;
  saveDisabled?: boolean;
  processing?: boolean;
  actionItems?: ActionItem[];
  additionalActions?: ActionItem[];
  additionalButtons?: ActionItemButton[];
}

type Story = StoryObj<FormActionsStoryArgs>;

function render(args: FormActionsStoryArgs) {
  return {
    components: {FormActions},
    setup() {
      const form = useForm({title: 'Spring menu', enabled: true});
      form.processing = args.processing ?? false;
      return {args, form};
    },
    template: `
      <form @submit.prevent="form.post('/admin/menus/save')">
        <FormActions
          :form="form"
          :submit-label="args.submitLabel"
          :read-only="args.readOnly"
          :save-disabled="args.saveDisabled"
          :action-items="args.actionItems"
          :additional-actions="args.additionalActions"
          :additional-buttons="args.additionalButtons"
        />
      </form>
    `,
  };
}

export const Default: Story = {
  render,
};

/** `action-items` adds a menu of other ways to save beside the Save button. */
export const WithSaveMenu: Story = {
  args: {
    actionItems: [
      {label: 'Save and continue editing', onClick: () => {}},
      {label: 'Save as a new menu', onClick: () => {}},
    ],
  },
  render,
};

/**
 * `additional-buttons` sit beside Save, and `additional-actions` go in a menu
 * at the end.
 */
export const WithExtraActions: Story = {
  args: {
    submitLabel: 'Publish',
    additionalButtons: [
      {label: 'Save draft', icon: 'floppy-disk', onClick: () => {}},
    ],
    additionalActions: [
      {label: 'Duplicate', icon: 'clone', onClick: () => {}},
      {type: 'hr'},
      {
        label: 'Delete menu',
        icon: 'trash',
        variant: 'danger',
        onClick: () => {},
      },
    ],
  },
  render,
};

/** While the form is processing, the buttons are disabled and Save spins. */
export const Saving: Story = {
  args: {processing: true},
  argTypes: {processing: {control: false}},
  render,
};
