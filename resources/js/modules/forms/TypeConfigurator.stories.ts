import {computed, shallowRef} from 'vue';
import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {setup} from '@storybook/vue3-vite';
import FieldNode from './FieldNode.vue';
import TextControl from './TextControl.vue';
import ComboboxControl from './ComboboxControl.vue';
import TypeConfigurator from './TypeConfigurator.vue';
import type {FormPayload} from './types';

setup((app) => {
  app.component('craft:field', FieldNode);
  app.component('craft:text', TextControl);
  app.component('craft:combobox', ComboboxControl);
});

const types = [
  {value: 'user-review', label: 'User review'},
  {value: 'automatic-approval', label: 'Automatic approval'},
];

const userReviewForm: FormPayload = {
  scope: ['settings'],
  refreshable: false,
  nodes: [
    {
      type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
      component: 'craft:field',
      props: {label: 'Reviewer group', required: true},
      control: {
        type: 'CraftCms\\Cms\\Form\\Controls\\Combobox',
        component: 'craft:combobox',
        props: {
          options: [
            {label: 'Editors', value: 'editors'},
            {label: 'Publishers', value: 'publishers'},
          ],
        },
        path: ['settings', 'reviewerGroup'],
        mode: 'editable',
        deltaGroup: ['settings', 'reviewerGroup'],
      },
    },
    {
      type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
      component: 'craft:field',
      props: {label: 'Approvals required', required: true},
      control: {
        type: 'CraftCms\\Cms\\Form\\Controls\\Number',
        component: 'craft:text',
        props: {inputType: 'number', min: 1},
        path: ['settings', 'approvalsRequired'],
        mode: 'editable',
        deltaGroup: ['settings', 'approvalsRequired'],
      },
    },
  ],
  values: {
    settings: {
      reviewerGroup: 'editors',
      approvalsRequired: 1,
    },
  },
  errors: [],
  globalErrors: [],
};

const meta = {
  title: 'Forms/TypeConfigurator',
  component: TypeConfigurator,
  args: {
    types,
    selectedTypeLabel: 'User review',
    form: userReviewForm,
  },
  parameters: {
    docs: {
      description: {
        component:
          'Combines a type picker with the dynamic form for configuring the selected type.',
      },
    },
  },
} satisfies Meta<typeof TypeConfigurator>;

export default meta;
type Story = StoryObj<typeof meta>;

function render(initialType = 'user-review') {
  return (args: Story['args']) => ({
    components: {TypeConfigurator},
    setup() {
      const selectedType = shallowRef(initialType);
      const selectedTypeLabel = computed(
        () =>
          types.find((type) => type.value === selectedType.value)?.label ?? ''
      );
      const form = computed(() =>
        selectedType.value === 'user-review' ? userReviewForm : null
      );

      return {args, form, selectedType, selectedTypeLabel, types};
    },
    template: `
      <TypeConfigurator
        v-bind="args"
        :key="selectedType"
        :types="types"
        :selected-type-label="selectedTypeLabel"
        :form="form"
        @select="selectedType = $event"
      />
    `,
  });
}

export const Default: Story = {
  args: {typeLabel: 'Type'},
  render: render(),
};

export const AutomaticApproval: Story = {
  args: {typeLabel: 'Type'},
  render: render('automatic-approval'),
};

export const Disabled: Story = {
  args: {typeLabel: 'Type', disabled: true},
  render: render(),
};

export const Narrow: Story = {
  args: {typeLabel: 'Type'},
  decorators: [
    () => ({
      template: '<div style="max-width: 20rem"><story /></div>',
    }),
  ],
  render: render(),
};
