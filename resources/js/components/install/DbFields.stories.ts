import type {Meta, StoryObj} from '@storybook/vue3-vite';

import DbFields from './DbFields.vue';

const meta = {
  title: 'Install/DbFields',
  component: DbFields,
  tags: ['autodocs'],
} satisfies Meta<typeof DbFields>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    modelValue: {
      driver: 'mysql',
      host: '127.0.0.1',
      port: '3306',
      username: 'root',
      password: '',
      database: 'craft',
      prefix: '',
    },
  },
};

export const WithErrors: Story = {
  args: {
    modelValue: {
      driver: 'mysql',
      host: '',
      port: '',
      username: '',
      password: '',
      database: '',
      prefix: '',
    },
    errors: {
      '*': ['Could not connect to the database.'],
      host: ['Host is required.'],
      database: ['Database name is required.'],
    },
  },
};
