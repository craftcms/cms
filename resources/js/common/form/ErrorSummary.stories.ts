import type {Meta, StoryObj} from '@storybook/vue3-vite';
import ErrorSummary from './ErrorSummary.vue';

const meta = {
  title: 'CP/ErrorSummary',
  component: ErrorSummary,
  args: {
    title: 'Could not save settings',
    errors: {
      handle: 'Handle “news” has already been taken.',
      'settings.apiKey': 'API key cannot be blank.',
    },
  },
  parameters: {
    docs: {
      description: {
        component:
          'A red callout listing the errors from a failed save. Show it at the ' +
          'top of the form when a save fails, and remove it when the next save ' +
          'starts. It takes focus when it appears, so screen readers announce ' +
          'it and it scrolls into view.\n\n' +
          'Each key in `errors` is the path of the value that failed, such as ' +
          '`settings.apiKey`. When the page has a field with the matching id ' +
          '(`ui-settings-apiKey`), the message becomes a link that jumps to ' +
          'that field, switching tabs if it needs to.\n\n' +
          "```ts\nimport {ErrorSummary} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof ErrorSummary>;

export default meta;

type Story = StoryObj<typeof meta>;

/** No field on the page matches these keys, so the messages are plain text. */
export const Default: Story = {};

/** An element editor says what it was trying to do. */
export const CustomTitle: Story = {
  args: {
    title: 'Couldn’t save entry.',
    errors: {
      title: 'Title cannot be blank.',
      postDate: 'Post Date must be before Expiry Date.',
    },
  },
};

/** Messages whose field is on the page link to it. */
export const LinkedToFields: Story = {
  args: {
    errors: {
      name: 'Name cannot be blank.',
      'settings.apiKey': 'API key must be 32 characters.',
    },
  },
  render: (args) => ({
    components: {ErrorSummary},
    setup() {
      return {args};
    },
    template: `
      <ErrorSummary v-bind="args" />
      <craft-field id="ui-name" label="Name" has-errors>
        <craft-input slot="input" label="Name"></craft-input>
      </craft-field>
      <div style="height: 40rem"></div>
      <craft-field id="ui-settings-apiKey" label="API key" has-errors>
        <craft-input slot="input" label="API key" .value="'sk_live_51H'"></craft-input>
      </craft-field>
    `,
  }),
};
