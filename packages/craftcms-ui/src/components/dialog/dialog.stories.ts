import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftDialog from './dialog.js';
import {expect} from 'storybook/test';
import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import './dialog.js';
import '../button/button.js';

const {events, args, argTypes, template} = getStorybookHelpers('craft-dialog');

/**
 * `#dispatch(name)` builds its events from a variable, so the analyzer records
 * the parameter itself as an event. Dropping it keeps the Actions panel to the
 * four real lifecycle events.
 */
const dialogEvents = events.filter((event) => event !== 'name');

const body = html`This is some text within a dialog.`;

const footerClose = html`
  <craft-button slot="footer" data-dialog="close">Close</craft-button>
`;

const meta: Meta<CraftDialog & typeof args> = {
  title: 'Components/Dialog',
  component: 'craft-dialog',
  // Everything but these two comes from the manifest, so the controls stay in
  // step with the component's attributes on their own.
  args: {...args, open: true, label: 'Dialog'},
  argTypes,
  render: (args) => template(args, html`${body}${footerClose}`),
  parameters: {
    layout: 'centered',
    actions: {handles: dialogEvents},
  },
};

export default meta;
type Story = StoryObj<CraftDialog & typeof args>;

export const Default: Story = {};

/** No label, but the close button keeps the header — it needs somewhere to live. */
export const NoLabel: Story = {
  args: {label: ''},
  async play({canvasElement}) {
    const dialog = canvasElement.querySelector('craft-dialog') as CraftDialog;
    await dialog.updateComplete;

    await expect(dialog.shadowRoot!.querySelector('.header')).not.toBeNull();
    await expect(dialog.shadowRoot!.querySelector('.close')).not.toBeNull();
  },
};

/**
 * Neither a label nor a close button, so the header goes entirely rather than
 * leaving a band of padding above the body. Dismissal comes from the footer.
 */
export const NoHeader: Story = {
  args: {label: '', 'no-close': true},
  async play({canvasElement}) {
    const dialog = canvasElement.querySelector('craft-dialog') as CraftDialog;
    await dialog.updateComplete;

    // Also proves the manifest-derived args actually reach the component.
    await expect(dialog.noClose).toBe(true);
    await expect(dialog.shadowRoot!.querySelector('.header')).toBeNull();
  },
};

/** No footer slotted — the footer row collapses rather than leaving a gap. */
export const NoFooter: Story = {
  render: (args) => template(args, body),
};

/**
 * Opened with `show()` rather than `showModal()`, so the dialog stays out of the
 * top layer and content that appends itself to `<body>` — most legacy CP menus —
 * still paints above it. The backdrop, Escape and focus containment are the
 * component's own here.
 */
export const NonModal: Story = {
  args: {'non-modal': true},
  async play({canvasElement}) {
    const dialog = canvasElement.querySelector('craft-dialog') as CraftDialog;
    await dialog.updateComplete;

    await expect(
      dialog.shadowRoot!.querySelector('dialog')!.matches(':modal')
    ).toBe(false);
  },
};

export const Fullscreen: Story = {
  args: {fullscreen: true},
};

/** Long content scrolls inside the body rather than growing the surface. */
export const Scrolling: Story = {
  render: (args) =>
    template(
      args,
      html`
        ${Array.from(
          {length: 40},
          (_, i) => `Line ${i + 1} of a dialog body that has to scroll. `
        ).join('')}
        ${footerClose}
      `
    ),
};

export const ClosesOnOutsideClick: Story = {
  args: {'close-on-outside-click': true},
};
