import type {Meta, StoryObj} from '@storybook/vue3-vite';
import CalloutReadOnly from './CalloutReadOnly.vue';

const meta = {
  title: 'CP/CalloutReadOnly',
  component: CalloutReadOnly,
  parameters: {
    docs: {
      description: {
        component:
          'A banner that tells people the settings below can’t be changed ' +
          'here, such as when admin changes are turned off in this ' +
          'environment. It spans the full width of its container, so place ' +
          'it at the top of a page or slideout body, above the fields it ' +
          'covers. The default slot replaces the standard message.\n\n' +
          "```ts\nimport {CalloutReadOnly} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof CalloutReadOnly>;

export default meta;
type Story = StoryObj<typeof meta>;

/** The standard message. */
export const Default: Story = {
  render: () => ({
    components: {CalloutReadOnly},
    template: '<CalloutReadOnly />',
  }),
};

/** A message of your own, for a reason specific to your plugin. */
export const CustomMessage: Story = {
  render: () => ({
    components: {CalloutReadOnly},
    template: `
      <CalloutReadOnly>
        These feed settings are managed by your hosting provider.
      </CalloutReadOnly>
    `,
  }),
};
