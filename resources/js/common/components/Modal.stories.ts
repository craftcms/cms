import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {ref} from 'vue';
import Modal from './Modal.vue';

const widths = [
  'sm',
  'md',
  'lg',
  'xl',
  '2xl',
  '3xl',
  '4xl',
  '5xl',
  '6xl',
  '7xl',
];

const meta = {
  title: 'CP/Modal',
  component: Modal,
  args: {
    label: 'Deprecation warning',
    width: 'md',
    overlay: true,
    resizable: false,
    dismissible: true,
    restoreFocus: true,
    instant: false,
  },
  argTypes: {
    isActive: {control: false},
    width: {control: 'select', options: widths},
  },
  parameters: {
    docs: {
      description: {
        component:
          'A dialog box shown over the page. You decide what goes inside it, ' +
          'and you open and close it yourself with `is-active`. It emits ' +
          '`close` when the user presses Escape or clicks the overlay, so set ' +
          'your state back to `false` there. Modals opened from inside another ' +
          'modal stack on top of it, and Escape closes the top one first.\n\n' +
          'For a modal with a title, a form and Save/Cancel buttons, use ' +
          '`ModalForm` instead.\n\n' +
          "```ts\nimport {Modal} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof Modal>;

export default meta;

type Story = StoryObj<typeof meta>;

function render(content: string, opener = 'View details') {
  return (args: Story['args']) => ({
    components: {Modal},
    setup() {
      const open = ref(false);
      return {args, open};
    },
    template: `
      <craft-button type="button" @click="open = true">${opener}</craft-button>
      <Modal v-bind="args" :is-active="open" @close="open = false">
        ${content}
      </Modal>
    `,
  });
}

export const Default: Story = {
  render: render(`
    <craft-pane label="Deprecation warning">
      <craft-button
        slot="header-actions"
        type="button"
        icon="xmark-large"
        aria-label="Close"
        variant="plain"
        size="small"
        @click="open = false"
      ></craft-button>
      <p>
        <code>craft.entries.find()</code> is deprecated. Use
        <code>craft.entries.all()</code> instead.
      </p>
      <p>Last seen in <code>templates/news/_entry.twig</code>, line 14.</p>
      <craft-button
        slot="primary-action"
        type="button"
        variant="primary"
        @click="open = false"
      >Done</craft-button>
    </craft-pane>
  `),
};

/** A wide modal the user can drag larger or smaller from its edges. */
export const WideAndResizable: Story = {
  args: {
    label: 'Stack trace',
    width: '3xl',
    resizable: true,
  },
  render: render(
    `
    <craft-pane label="Stack trace">
      <pre class="text-sm">#0 vendor/craftcms/cms/src/Element/Queries/EntryQuery.php(212): find()
#1 storage/runtime/compiled_templates/4f/4f2a1c.php(48): one()
#2 vendor/twig/twig/src/Template.php(394): doDisplay()
#3 vendor/twig/twig/src/Template.php(367): displayWithErrorHandling()
#4 vendor/craftcms/cms/src/View/TemplateRenderer.php(88): render()</pre>
      <craft-button
        slot="primary-action"
        type="button"
        variant="primary"
        @click="open = false"
      >Close</craft-button>
    </craft-pane>
  `,
    'View stack trace'
  ),
};

/**
 * With `dismissible` off, Escape and clicks on the overlay do nothing. Use it
 * when closing would throw away work, so only the modal's own buttons close it.
 */
export const NotDismissible: Story = {
  args: {
    label: 'Session expired',
    width: 'sm',
    dismissible: false,
  },
  render: render(
    `
    <craft-pane label="Your session has expired">
      <p>Sign in again to keep editing. Your unsaved changes are still here.</p>
      <craft-button
        slot="secondary-action"
        type="button"
        variant="plain"
        @click="open = false"
      >Sign out</craft-button>
      <craft-button
        slot="primary-action"
        type="button"
        variant="primary"
        @click="open = false"
      >Sign in</craft-button>
    </craft-pane>
  `,
    'Expire session'
  ),
};

/** A modal opened from inside another stacks on top of it. */
export const Nested: Story = {
  args: {label: 'Choose an icon'},
  render: (args) => ({
    components: {Modal},
    setup() {
      const outer = ref(false);
      const inner = ref(false);
      return {args, outer, inner};
    },
    template: `
      <craft-button type="button" @click="outer = true">Edit page settings</craft-button>
      <Modal :is-active="outer" label="Page settings" @close="outer = false">
        <craft-pane label="Page settings">
          <p>Pick an icon to show beside this page in the sidebar.</p>
          <craft-button type="button" @click="inner = true">Choose icon…</craft-button>
          <craft-button
            slot="primary-action"
            type="button"
            variant="primary"
            @click="outer = false"
          >Done</craft-button>
        </craft-pane>
      </Modal>
      <Modal v-bind="args" width="sm" :is-active="inner" @close="inner = false">
        <craft-pane label="Choose an icon">
          <p>Press Escape to close this modal. The one beneath stays open.</p>
          <craft-button
            slot="primary-action"
            type="button"
            variant="primary"
            @click="inner = false"
          >Use this icon</craft-button>
        </craft-pane>
      </Modal>
    `,
  }),
};
