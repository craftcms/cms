import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {ref} from 'vue';
import CraftCombobox from './CraftCombobox.vue';
import type {SelectItem} from '@/common/types';

const templates: SelectItem[] = [
  {
    type: 'optgroup',
    label: 'Pages',
    options: [
      {label: '_pages/about', value: '_pages/about'},
      {label: '_pages/contact', value: '_pages/contact'},
      {label: '_pages/home', value: '_pages/home'},
    ],
  },
  {
    type: 'optgroup',
    label: 'Blog',
    options: [
      {label: 'blog/_entry', value: 'blog/_entry'},
      {label: 'blog/_index', value: 'blog/_index'},
    ],
  },
];

const languages: SelectItem[] = [
  {label: 'English', value: 'en'},
  {label: 'Deutsch', value: 'de'},
  {label: 'Español', value: 'es'},
  {label: 'Français', value: 'fr'},
  {label: 'Italiano', value: 'it'},
  {label: '日本語', value: 'ja'},
  {label: 'Nederlands', value: 'nl'},
  {label: 'Português', value: 'pt'},
];

const urlSuggestions: SelectItem[] = [
  {
    type: 'optgroup',
    label: 'Environment Variables',
    options: [
      {label: '$PRIMARY_SITE_URL', value: '$PRIMARY_SITE_URL'},
      {label: '$CDN_URL', value: '$CDN_URL'},
    ],
  },
  {
    type: 'optgroup',
    label: 'Aliases',
    options: [{label: '@web', value: '@web'}],
  },
];

interface CraftComboboxStoryArgs {
  label: string;
  name?: string;
  options?: SelectItem[];
  placeholder?: string;
  error?: string;
  disabled?: boolean;
  clearable?: boolean;
  requireOptionMatch?: boolean;
  showAllOnEmpty?: boolean;
  callouts?: Array<'envVars'>;
}

const meta = {
  title: 'CP/Combobox',
  component: CraftCombobox,
  args: {
    label: 'Template',
    name: 'template',
    options: templates,
    placeholder: undefined,
    error: undefined,
    disabled: false,
    clearable: false,
    requireOptionMatch: false,
    showAllOnEmpty: true,
  },
  argTypes: {
    error: {control: 'text'},
    placeholder: {control: 'text'},
  },
  parameters: {
    docs: {
      description: {
        component:
          'A text input that suggests values from a list as you type, bound ' +
          'with `v-model` as a string. By default any text is allowed and ' +
          'the options are only suggestions. Set `requireOptionMatch` to ' +
          'limit the value to one of the options. Options can be grouped ' +
          'with `{type: "optgroup", label, options}`.\n\n' +
          'With `callouts: ["envVars"]` a tip under the input says the value ' +
          'can begin with an environment variable. The `after` slot replaces ' +
          'that tip with your own content.\n\n' +
          "Plugins import it from the control panel: `import {Combobox} from '@craftcms/cp';`",
      },
    },
  },
} satisfies Meta<CraftComboboxStoryArgs>;

export default meta;

type Story = StoryObj<CraftComboboxStoryArgs>;

function render(initial: string) {
  return (args: CraftComboboxStoryArgs) => ({
    components: {CraftCombobox},
    setup() {
      const value = ref(initial);
      return {args, value};
    },
    template: `
      <div class="flex flex-col gap-4">
        <CraftCombobox v-bind="args" v-model="value" />
        <p class="text-xs">Value: <code>{{ value }}</code></p>
      </div>
    `,
  });
}

/** Suggests templates, but takes any path. */
export const Default: Story = {
  render: render('blog/_entry'),
};

/** `requireOptionMatch` only accepts one of the listed languages. */
export const RequireOptionMatch: Story = {
  args: {
    label: 'Language',
    name: 'language',
    options: languages,
    requireOptionMatch: true,
  },
  render: render('de'),
};

/** `callouts: ['envVars']` adds a tip about environment variables. */
export const EnvironmentVariables: Story = {
  args: {
    label: 'Base URL',
    name: 'baseUrl',
    options: urlSuggestions,
    callouts: ['envVars'],
  },
  render: render('$PRIMARY_SITE_URL'),
};

export const WithError: Story = {
  args: {
    error: 'Template “blog/_entri” doesn’t exist.',
  },
  render: render('blog/_entri'),
};
