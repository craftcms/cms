import type {Meta, StoryObj} from '@storybook/vue3-vite';
import CpLink from './CpLink.vue';

const meta = {
  title: 'CP/CpLink',
  component: CpLink,
  args: {
    href: '/admin/entries/news',
    icon: undefined,
    block: false,
    inertia: true,
    bare: false,
  },
  argTypes: {
    icon: {control: 'text'},
  },
  parameters: {
    docs: {
      description: {
        component:
          'A link to another control panel page. It loads the page through ' +
          'Inertia, so the shell stays in place and only the page content ' +
          'changes. Links to another host, and links with `inertia` turned ' +
          'off, are ordinary links the browser follows itself. Anything ' +
          'Inertia’s `<Link>` accepts, such as `method`, `data`, ' +
          '`preserveScroll` or `only`, passes straight through. GET links ' +
          'prefetch the page when the link is pressed.\n\n' +
          'Give `as` a custom element tag, such as `craft-nav-item`, to ' +
          'render the link as that element. For a link styled as a button, ' +
          'use `CpButtonLink`.\n\n' +
          "```ts\nimport {CpLink} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof CpLink>;

export default meta;
type Story = StoryObj<typeof meta>;

/** A plain link to another CP page. Clicking it logs the Inertia visit. */
export const Default: Story = {
  render: (args) => ({
    components: {CpLink},
    setup: () => ({args}),
    template: '<CpLink v-bind="args">View all news entries</CpLink>',
  }),
};

/** With an icon before the text, such as a link at the top of a settings page. */
export const WithIcon: Story = {
  ...Default,
  args: {
    href: '/admin/settings/sections',
    icon: 'arrow-left',
  },
  render: (args) => ({
    components: {CpLink},
    setup: () => ({args}),
    template: '<CpLink v-bind="args">Back to sections</CpLink>',
  }),
};

/**
 * `bare` drops the flex wrapper, so a link inside a sentence wraps with the
 * text around it.
 */
export const InText: Story = {
  args: {
    href: '/admin/settings/email',
    bare: true,
  },
  render: (args) => ({
    components: {CpLink},
    setup: () => ({args}),
    template: `
      <p style="max-inline-size: 32rem">
        Craft couldn’t send the password reset email. Check the transport
        settings in <CpLink v-bind="args">Email settings</CpLink> and send a
        test message to confirm they work.
      </p>
    `,
  }),
};

/**
 * A link to another host leaves the control panel, so it skips Inertia and
 * the browser follows it normally.
 */
export const External: Story = {
  args: {
    href: 'https://craftcms.com/docs',
    icon: 'book',
  },
  render: (args) => ({
    components: {CpLink},
    setup: () => ({args}),
    template: '<CpLink v-bind="args">Read the Craft documentation</CpLink>',
  }),
};
