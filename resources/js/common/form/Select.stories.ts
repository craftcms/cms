import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {ref} from 'vue';
import Select from './Select.vue';
import type {BaseOption} from '@/common/types';

const sections: BaseOption[] = [
  {label: 'Blog', value: 'blog'},
  {label: 'News', value: 'news'},
  {label: 'Recipes', value: 'recipes'},
  {label: 'Case Studies', value: 'caseStudies'},
];

interface SelectStoryArgs {
  label: string;
  options: BaseOption[] | string[];
  error?: string;
  disabled?: boolean;
}

const meta = {
  title: 'CP/Select',
  component: Select,
  args: {
    label: 'Section',
    options: sections,
    error: undefined,
    disabled: false,
  },
  argTypes: {
    error: {control: 'text'},
  },
  parameters: {
    docs: {
      description: {
        component:
          'A dropdown for choosing one value from a short list, bound with ' +
          '`v-model`. Options are `{label, value}` objects, or plain strings ' +
          'that serve as both. Attributes such as `label`, `name`, `id` and ' +
          '`disabled` pass through to the `<craft-select>` it renders.\n\n' +
          "Plugins import it from the control panel: `import {Select} from '@craftcms/cp';`",
      },
    },
  },
} satisfies Meta<SelectStoryArgs>;

export default meta;

type Story = StoryObj<SelectStoryArgs>;

function render(initial: string) {
  return (args: SelectStoryArgs) => ({
    components: {Select},
    setup() {
      const value = ref(initial);
      return {args, value};
    },
    template: `
      <div class="flex flex-col gap-4">
        <Select v-bind="args" v-model="value" />
        <p class="text-xs">Selected: <code>{{ value }}</code></p>
      </div>
    `,
  });
}

export const Default: Story = {
  render: render('news'),
};

/** Plain strings are their own label and value. */
export const StringOptions: Story = {
  args: {
    label: 'Week starts on',
    options: ['Sunday', 'Monday', 'Saturday'],
  },
  render: render('Monday'),
};

/** `error` shows a message under the control. */
export const WithError: Story = {
  args: {
    label: 'Default section',
    error: 'Choose a section that has an entry type.',
  },
  render: render('caseStudies'),
};

export const Disabled: Story = {
  args: {disabled: true},
  render: render('blog'),
};
