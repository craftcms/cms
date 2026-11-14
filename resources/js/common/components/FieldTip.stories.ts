import type {Meta, StoryObj} from '@storybook/vue3-vite';
import FieldTip from './FieldTip.vue';

const meta = {
  title: 'CP/FieldTip',
  component: FieldTip,
  args: {
    message: 'Type <kbd>$</kbd> to choose an environment variable.',
    html: true,
    href: 'https://craftcms.com/docs/config/#environmental-configuration',
  },
  parameters: {
    docs: {
      description: {
        component:
          'The text of a field’s tip, with an optional “Learn more” link. ' +
          'Put it in a field’s `tip` slot with `slot="tip"`. The field shows ' +
          'it as a tip below the control and adds it to the control’s ' +
          'accessible description.\n\n' +
          'Pass a translated `message`. Set `html` when the message contains ' +
          'trusted markup, such as `<kbd>`. Never set it for text that came ' +
          'from a user.\n\n' +
          "```ts\nimport {FieldTip} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof FieldTip>;

export default meta;
type Story = StoryObj<typeof meta>;

const render = (args: Record<string, unknown>) => ({
  components: {FieldTip},
  setup: () => ({args}),
  template: `
    <craft-input label="Base URL" name="baseUrl" value="$PRIMARY_SITE_URL">
      <FieldTip slot="tip" v-bind="args" />
    </craft-input>
  `,
});

/** A tip with markup and a link to the docs, below a field. */
export const Default: Story = {render};

/** Just a plain message. */
export const PlainText: Story = {
  render,
  args: {
    message: 'Leave this blank to use the system email address.',
    html: false,
    href: undefined,
  },
};
