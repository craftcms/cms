import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {ref} from 'vue';
import IconPicker from './IconPicker.vue';

interface IconPickerStoryArgs {
  label?: string;
  name?: string;
  error?: string;
  disabled?: boolean;
  freeOnly?: boolean;
  sets?: string[] | '*' | null;
}

const meta = {
  title: 'CP/IconPicker',
  component: IconPicker,
  args: {
    label: 'Icon',
    name: 'icon',
    error: undefined,
    disabled: false,
    freeOnly: false,
  },
  argTypes: {
    error: {control: 'text'},
  },
  parameters: {
    docs: {
      description: {
        component:
          'Lets an author choose an icon, bound with `v-model` as the icon’s ' +
          'name. The Choose button opens a searchable modal of icons, which ' +
          'it loads from the control panel. Icons from a custom icon set are ' +
          'stored as `set-handle:name`. `sets` picks which icon sets to ' +
          'offer, or `"*"` for all of them. Font Awesome is the default. ' +
          '`freeOnly` leaves out Font Awesome Pro icons.\n\n' +
          'Storybook has no control panel to load icons from, so these ' +
          'stories show the picker with no icon chosen, and its modal opens ' +
          'empty.\n\n' +
          "Plugins import it from the control panel: `import {IconPicker} from '@craftcms/cp';`",
      },
    },
  },
} satisfies Meta<IconPickerStoryArgs>;

export default meta;

type Story = StoryObj<IconPickerStoryArgs>;

function render(args: IconPickerStoryArgs) {
  return {
    components: {IconPicker},
    setup() {
      const icon = ref('');
      return {args, icon};
    },
    template: `
      <div class="flex flex-col gap-4">
        <IconPicker v-bind="args" v-model="icon" />
        <p class="text-xs">Value: <code>{{ icon || '(none)' }}</code></p>
      </div>
    `,
  };
}

export const Default: Story = {
  render,
};

/** `error` marks the input as invalid and shows the message under it. */
export const WithError: Story = {
  args: {error: 'Choose an icon for this section.'},
  render,
};

export const Disabled: Story = {
  args: {disabled: true},
  render,
};
