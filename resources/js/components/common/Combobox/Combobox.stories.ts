import type {Meta, StoryObj} from '@storybook/vue3-vite';

import Combobox from './Combobox.vue';

const meta = {
  title: 'Components/Combobox',
  component: Combobox,
  tags: ['autodocs'],
  argTypes: {
    modelValue: {control: 'text'},
  },
} satisfies Meta<typeof Combobox>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    label: 'Choose a color',
    id: 'color-combobox',
    name: 'color',
    options: [
      {value: 'red', label: 'Red'},
      {value: 'green', label: 'Green'},
      {value: 'blue', label: 'Blue'},
    ],
  },
};

export const WithHelpText: Story = {
  args: {
    label: 'Language',
    id: 'language-combobox',
    name: 'language',
    helpText: 'Select your preferred language.',
    options: [
      {value: 'en', label: 'English'},
      {value: 'fr', label: 'French'},
      {value: 'de', label: 'German'},
    ],
  },
};

export const WithError: Story = {
  args: {
    label: 'Country',
    id: 'country-combobox',
    name: 'country',
    error: 'This field is required.',
    options: [
      {value: 'us', label: 'United States'},
      {value: 'ca', label: 'Canada'},
    ],
  },
};

export const Grouped: Story = {
  args: {
    label: 'Timezone',
    id: 'tz-combobox',
    name: 'timezone',
    options: [
      {
        label: 'Americas',
        data: [
          {name: 'America/New_York', hint: 'Eastern'},
          {name: 'America/Chicago', hint: 'Central'},
          {name: 'America/Los_Angeles', hint: 'Pacific'},
        ],
      },
      {
        label: 'Europe',
        data: [
          {name: 'Europe/London', hint: 'GMT'},
          {name: 'Europe/Paris', hint: 'CET'},
        ],
      },
    ],
  },
};
