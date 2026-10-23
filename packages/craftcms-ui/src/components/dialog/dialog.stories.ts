import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftDialog from './dialog.js';
import {expect} from 'storybook/test';
import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import './dialog.js';
import '../button/button.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `dialog.ts` surfaces it here without touching this file.
 */
const {events, args, argTypes, template} = getStorybookHelpers('craft-dialog');

// Boolean attributes whose name differs from their property (`no-close` and
// `noClose`) are set by property name: the helpers generate an arg for the
// property too, and apply it after the attribute, so its default would win.

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

/** Renders the dialog after a button that opens it. */
function withOpener(
  args: Parameters<typeof template>[0],
  content: Parameters<typeof template>[1]
) {
  return html`
    <craft-button
      @click=${(event: Event) => {
        const dialog = (
          event.currentTarget as HTMLElement
        ).parentElement!.querySelector('craft-dialog') as CraftDialog;
        dialog.opened = true;
      }}
    >
      Open dialog
    </craft-button>
    ${template(args, content)}
  `;
}

const meta: Meta<CraftDialog & typeof args> = {
  title: 'Components/Dialog',
  component: 'craft-dialog',
  // Everything but these two comes from the manifest, so the controls stay in
  // step with the component's attributes on their own.
  args: {...args, label: 'Dialog'},
  argTypes,
  render: (args) => withOpener(args, html`${body}${footerClose}`),
  parameters: {
    layout: 'centered',
    actions: {handles: dialogEvents},
  },
};

export default meta;
type Story = StoryObj<CraftDialog & typeof args>;

export const Default: Story = {};

/**
 * The content draws its own title, so `no-header` drops the header row, close
 * button included. The `label` still names the dialog, and dismissal comes
 * from the footer.
 */
export const NoHeader: Story = {
  args: {label: 'Dialog with its own title', noHeader: true},
  render: (args) =>
    withOpener(
      args,
      html`
        <h2>Dialog with its own title</h2>
        ${body}${footerClose}
      `
    ),
  async play({canvasElement}) {
    const dialog = canvasElement.querySelector('craft-dialog') as CraftDialog;
    await dialog.updateComplete;

    // Also proves the manifest-derived args actually reach the component.
    await expect(dialog.noHeader).toBe(true);

    const root = dialog.shadowRoot!;
    await expect(root.querySelector('.header')).toBeNull();
    const labelledBy = root
      .querySelector('dialog')!
      .getAttribute('aria-labelledby');
    await expect(
      root.getElementById(labelledBy ?? '')?.textContent?.trim()
    ).toBe('Dialog with its own title');
  },
};

/** No footer slotted — the footer row collapses rather than leaving a gap. */
export const NoFooter: Story = {
  render: (args) => withOpener(args, body),
};

/**
 * Opened with `show()` rather than `showModal()`, so the dialog stays out of the
 * top layer and content that appends itself to `<body>` — most legacy CP menus —
 * still paints above it. The backdrop, Escape and focus containment are the
 * component's own here.
 */
export const NonModal: Story = {
  args: {nonModal: true},
  async play({canvasElement}) {
    const dialog = canvasElement.querySelector('craft-dialog') as CraftDialog;
    await dialog.updateComplete;

    await expect(
      dialog.shadowRoot!.querySelector('dialog')!.matches(':modal')
    ).toBe(false);
  },
};

/**
 * Fills the viewport, so the body row can hand a definite height down to
 * whatever it slots — a canvas, say, that measures its container to decide how
 * big to draw.
 */
export const Fullscreen: Story = {
  args: {fullscreen: true, opened: true},
  async play({canvasElement}) {
    const dialog = canvasElement.querySelector('craft-dialog') as CraftDialog;
    await dialog.updateComplete;

    const surface = dialog.shadowRoot!.querySelector('.surface')!;
    const {width, height} = surface.getBoundingClientRect();

    // The height matters: `max-block-size` alone would leave it as short as
    // its content.
    await expect(Math.round(width)).toBe(window.innerWidth);
    await expect(Math.round(height)).toBe(window.innerHeight);
  },
};

/** Long content scrolls inside the body rather than growing the surface. */
export const Scrolling: Story = {
  render: (args) =>
    withOpener(
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
  args: {closeOnOutsideClick: true},
};

/**
 * A dialog can carry its own theme. `[data-theme]` re-resolves the semantic
 * tokens against the palette it names, so everything slotted in follows —
 * including each component's shadow root, since custom properties inherit past
 * the boundary.
 */
export const Themed: Story = {
  args: {label: 'Dark dialog', opened: true},
  render: (args) =>
    template({...args, 'data-theme': 'dark'}, html`${body}${footerClose}`),
  async play({canvasElement}) {
    const dialog = canvasElement.querySelector('craft-dialog') as CraftDialog;
    await dialog.updateComplete;

    const inside = getComputedStyle(dialog);
    const outside = getComputedStyle(canvasElement);

    // The semantic tokens re-resolve too: `var()` is substituted where it's
    // declared.
    await expect(inside.getPropertyValue('--color-base-50').trim()).not.toBe(
      outside.getPropertyValue('--color-base-50').trim()
    );
    await expect(inside.getPropertyValue('--c-surface-raised').trim()).not.toBe(
      outside.getPropertyValue('--c-surface-raised').trim()
    );
    await expect(
      inside.getPropertyValue('--c-color-neutral-fill-quiet').trim()
    ).not.toBe(outside.getPropertyValue('--c-color-neutral-fill-quiet').trim());
  },
};
