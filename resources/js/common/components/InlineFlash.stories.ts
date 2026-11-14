import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {ref} from 'vue';
import InlineFlash from './InlineFlash.vue';
import {showMessage, type MessageType} from '@/modules/messages/messages';

const meta = {
  title: 'CP/InlineFlash',
  component: InlineFlash,
  args: {
    target: 'email-test',
    busy: false,
  },
  parameters: {
    docs: {
      description: {
        component:
          'Shows a message next to the control that caused it, instead of ' +
          'in the corner of the screen. Give it a `target` name, then send ' +
          'messages to that name: on the client, pass `target` to ' +
          '`showMessage()`; for messages the server flashes while handling a ' +
          'request, send the target in the request’s ' +
          '`X-Craft-Message-Target` header. Messages sent to a name with no ' +
          'outlet on the page appear in the usual place.\n\n' +
          'Errors stay until the next message, or until `busy` turns on ' +
          'again, so pass the form’s `processing` state. Other messages ' +
          'clear on their own. Screen readers announce errors right away ' +
          'and other messages when they’re free.\n\n' +
          "```ts\nimport {InlineFlash} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof InlineFlash>;

export default meta;
type Story = StoryObj<typeof meta>;

const messages: Record<MessageType, string> = {
  success: 'Test email sent to brandon@example.com.',
  notice: 'The test email is queued and will go out shortly.',
  error: 'Couldn’t connect to smtp.example.com on port 587.',
};

/**
 * Each button sends a message to the outlet's target, as a request would
 * when it finishes.
 */
export const Default: Story = {
  render: (args) => ({
    components: {InlineFlash},
    setup() {
      const send = (type: MessageType) =>
        showMessage({
          type,
          message: messages[type],
          target: args.target as string,
        });

      return {args, send};
    },
    template: `
      <div class="flex flex-wrap items-center gap-2">
        <craft-button type="button" @click="send('success')">Send success</craft-button>
        <craft-button type="button" @click="send('notice')">Send notice</craft-button>
        <craft-button type="button" @click="send('error')">Send error</craft-button>
        <InlineFlash v-bind="args" />
      </div>
    `,
  }),
};

/**
 * Beside a form's submit button. Submitting turns `busy` on, which clears
 * the last message, and the result arrives a moment later.
 */
export const BesideASubmitButton: Story = {
  args: {target: 'find-replace'},
  render: (args) => ({
    components: {InlineFlash},
    setup() {
      const busy = ref(false);
      let failNext = false;

      function submit() {
        busy.value = true;

        setTimeout(() => {
          busy.value = false;
          showMessage(
            failNext
              ? {
                  type: 'error',
                  message: 'Enter some text to find.',
                  target: args.target as string,
                }
              : {
                  type: 'success',
                  message: 'Replaced 14 occurrences across 6 entries.',
                  target: args.target as string,
                }
          );
          failNext = !failNext;
        }, 800);
      }

      return {args, busy, submit};
    },
    template: `
      <div class="flex items-center gap-2">
        <craft-button type="button" variant="primary" :loading="busy" @click="submit">
          Replace
        </craft-button>
        <InlineFlash :target="args.target" :busy="busy" />
      </div>
    `,
  }),
};
