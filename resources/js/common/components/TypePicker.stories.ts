import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {ref} from 'vue';
import TypePicker from './TypePicker.vue';
import type {TypePickerOption} from './typePicker';

const types: TypePickerOption[] = [
  {value: 'painting', label: 'Painting', group: 'Works'},
  {value: 'sculpture', label: 'Sculpture', group: 'Works'},
  {value: 'photograph', label: 'Photograph', group: 'Works'},
  {value: 'talk', label: 'Talk', hint: 'talk', showHint: true, group: 'Events'},
  {value: 'tour', label: 'Tour', hint: 'tour', showHint: true, group: 'Events'},
];

const meta = {
  title: 'CP/TypePicker',
  component: TypePicker,
  args: {types, label: 'Add an item', adding: true, disabled: false},
  parameters: {
    docs: {
      description: {
        component:
          'A button that opens a searchable menu of types, and emits `select` ' +
          'with the picked option’s `value`. It’s how Craft adds a condition ' +
          'rule or picks a type in a type configurator.\n\n' +
          'Options with the same `group` are listed together under it. ' +
          '`showHint` adds the `hint` after the label, such as a handle. ' +
          '`adding` gives the button a plus icon and a dashed outline; ' +
          '`buttonVariant` sets any other variant.\n\n' +
          "```ts\nimport {TypePicker, type TypePickerOption} from '@craftcms/cp';\n```",
      },
    },
  },
  render: (args) => ({
    components: {TypePicker},
    setup() {
      const picked = ref<string | null>(null);
      return {args, picked};
    },
    template: `
      <div>
        <TypePicker v-bind="args" @select="picked = $event" />
        <p v-if="picked" style="margin-top: 1rem">Picked: <code>{{ picked }}</code></p>
      </div>
    `,
  }),
} satisfies Meta<typeof TypePicker>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Adding: Story = {};

export const Picking: Story = {
  args: {label: 'Type', adding: false, buttonVariant: 'plain'},
};
