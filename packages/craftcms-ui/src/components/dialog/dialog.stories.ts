import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './dialog.js';
import type CraftDialog from './dialog.js';

interface DialogArgs {
  label: string;
  nonModal: boolean;
  fullscreen: boolean;
  closeOnOutsideClick: boolean;
  body: string;
}

const meta = {
  title: 'Components/Dialog',
  component: 'craft-dialog',
  args: {
    label: 'Dialog',
    nonModal: false,
    fullscreen: false,
    closeOnOutsideClick: false,
    body: 'This is some text within a dialog.',
  },
  parameters: {
    layout: 'centered',
  },
  render(args: DialogArgs) {
    const id = 'storybook-dialog';

    function open() {
      (document.getElementById(id) as CraftDialog).opened = true;
    }

    return html`
      <craft-dialog
        id=${id}
        label=${args.label}
        ?non-modal=${args.nonModal}
        ?fullscreen=${args.fullscreen}
        ?close-on-outside-click=${args.closeOnOutsideClick}
      >
        ${args.body}

        <craft-button slot="footer" data-dialog="close">Close</craft-button>
      </craft-dialog>

      <craft-button @click=${open}>Open Dialog</craft-button>
    `;
  },
} satisfies Meta<DialogArgs>;

export default meta;
type Story = StoryObj<DialogArgs>;

export const Default: Story = {};

/** No footer slotted — the footer row collapses rather than leaving a gap. */
export const NoFooter: Story = {
  render(args: DialogArgs) {
    function open() {
      (document.getElementById('storybook-dialog-bare') as CraftDialog).opened =
        true;
    }

    return html`
      <craft-dialog id="storybook-dialog-bare" label=${args.label}>
        ${args.body}
      </craft-dialog>

      <craft-button @click=${open}>Open Dialog</craft-button>
    `;
  },
};

/**
 * Opened with `show()` rather than `showModal()`, so the dialog stays out of the
 * top layer and content that appends itself to `<body>` — most legacy CP menus —
 * still paints above it. The backdrop, Escape and focus containment are the
 * component's own here.
 */
export const NonModal: Story = {
  args: {nonModal: true},
};

export const Fullscreen: Story = {
  args: {fullscreen: true},
};

/** Long content scrolls inside the body rather than growing the surface. */
export const Scrolling: Story = {
  args: {
    body: Array.from(
      {length: 40},
      (_, i) => `Line ${i + 1} of a dialog body that has to scroll. `
    ).join(''),
  },
};

export const ClosesOnOutsideClick: Story = {
  args: {closeOnOutsideClick: true},
};
