import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {ref} from 'vue';
import CheckboxGroup from './CheckboxGroup.vue';
import type {CheckboxOption} from '@/common/types';

const notifications: CheckboxOption[] = [
  {label: 'When an entry is submitted for review', value: 'submitted'},
  {label: 'When an entry I wrote is published', value: 'published'},
  {label: 'When someone comments on my draft', value: 'comments'},
  {
    label: 'Weekly content summary',
    value: 'summary',
    info: 'Sent on Monday mornings in your time zone.',
  },
];

const sites: CheckboxOption[] = [
  {label: 'Acme (English)', value: 'en'},
  {label: 'Acme (Deutsch)', value: 'de'},
  {label: 'Acme (Français)', value: 'fr'},
  {label: 'Acme (日本語)', value: 'ja'},
];

const columns: CheckboxOption[] = [
  {label: 'Title', value: 'title', checked: true, disabled: true},
  {label: 'Status', value: 'status'},
  {label: 'Author', value: 'author'},
  {label: 'Post Date', value: 'postDate'},
  {label: 'Section', value: 'section'},
  {label: 'Date Updated', value: 'dateUpdated'},
];

interface CheckboxGroupStoryArgs {
  label?: string;
  name?: string;
  options: CheckboxOption[];
  disabled?: boolean;
  allowSelectAll?: boolean;
  sortable?: boolean;
}

const meta = {
  title: 'CP/CheckboxGroup',
  component: CheckboxGroup,
  args: {
    label: 'Email notifications',
    name: 'notifications',
    options: notifications,
    disabled: false,
    allowSelectAll: false,
    sortable: false,
  },
  parameters: {
    docs: {
      description: {
        component:
          'A set of checkboxes for choosing any number of values, bound ' +
          'with `v-model` as an array of the checked values. `allowSelectAll` ' +
          'adds an “All” checkbox. A `sortable` group lists only the checked ' +
          'options, offers the rest in an “Add item” menu, and reports the ' +
          'new order through `v-model:options`.\n\n' +
          'An option with `checked: true` always counts as checked, which ' +
          'suits a choice that can’t be turned off when it’s also `disabled`.\n\n' +
          "Plugins import it from the control panel: `import {CheckboxGroup} from '@craftcms/cp';`",
      },
    },
  },
} satisfies Meta<CheckboxGroupStoryArgs>;

export default meta;

type Story = StoryObj<CheckboxGroupStoryArgs>;

function render(initial: string[]) {
  return (args: CheckboxGroupStoryArgs) => ({
    components: {CheckboxGroup},
    setup() {
      const value = ref(initial);
      return {args, value};
    },
    template: `
      <div class="flex flex-col gap-4">
        <CheckboxGroup v-bind="args" v-model="value" />
        <p class="text-xs">Checked: <code>{{ value }}</code></p>
      </div>
    `,
  });
}

export const Default: Story = {
  render: render(['submitted', 'summary']),
};

/** `allowSelectAll` adds an “All” checkbox that checks every option at once. */
export const SelectAll: Story = {
  args: {
    label: 'Propagate to sites',
    name: 'sites',
    options: sites,
    allowSelectAll: true,
  },
  render: render(['en', 'de']),
};

/**
 * A sortable group lists the checked options in order, with the rest in an
 * “Add item” menu. `v-model:options` receives the options in their new order.
 * Title is checked and disabled, so it can’t be removed.
 */
export const Sortable: Story = {
  args: {
    label: 'Table columns',
    name: 'columns',
    sortable: true,
  },
  argTypes: {
    sortable: {control: false},
    options: {control: false},
  },
  render: (args) => ({
    components: {CheckboxGroup},
    setup() {
      const value = ref(['status', 'author', 'postDate']);
      const options = ref(columns);
      return {args, value, options};
    },
    template: `
      <div class="flex flex-col gap-4">
        <CheckboxGroup v-bind="args" v-model="value" v-model:options="options" />
        <p class="text-xs">Checked: <code>{{ value }}</code></p>
        <p class="text-xs">Order: <code>{{ options.map((option) => option.value) }}</code></p>
      </div>
    `,
  }),
};

export const Disabled: Story = {
  args: {disabled: true},
  render: render(['published']),
};
