import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftPane from './pane.js';
import {expect, waitFor} from 'storybook/test';
import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
const {events, args, argTypes, template} = getStorybookHelpers('craft-pane');
import './pane.js';
import '../button/button.js';
import '../icon/icon.js';

const bodyCopy = html`
  Panes are the primary CP content surface — a padded, rounded container with
  optional sticky header and footer regions.
`;

/** Enough YAML to push a `code` pane well past its 500px clamp. */
const longYaml = Array.from(
  {length: 60},
  (_, index) =>
    `entryTypes:\n  ${String(index).padStart(3, '0')}-a1b2c3d4:\n    handle: entryType${index}\n    name: Entry Type ${index}`
).join('\n');

const meta: Meta<CraftPane> = {
  title: 'Components/Pane',
  component: 'craft-pane',
  args: {
    ...args,
    label: '',
  },
  argTypes,
  render: (args) => template(args, bodyCopy),
  parameters: {
    actions: {
      handles: events,
    },
  },
};

export default meta;
type Story = StoryObj<CraftPane & typeof args>;

/** A bare pane: the `raised` appearance, `plain` variant, and `lg` padding. */
export const Default: Story = {
  args: {},
};

/**
 * `appearance` picks the surface treatment. `raised` is the default; `outline`
 * drops the shadow; `plain` falls back to the bare pane tokens; `sunken`
 * recesses the surface.
 */
export const Appearance: Story = {
  render: () => html`
    <div style="display: grid; gap: 1rem;">
      <craft-pane appearance="raised">appearance="raised" (default)</craft-pane>
      <craft-pane appearance="outline">appearance="outline"</craft-pane>
      <craft-pane appearance="plain">appearance="plain"</craft-pane>
      <craft-pane appearance="sunken">appearance="sunken"</craft-pane>
    </div>
  `,
};

/**
 * `variant` layers a semantic treatment on top of the appearance. The two are
 * orthogonal, so a `code` pane can also be `outline`.
 */
export const Variant: Story = {
  render: () => html`
    <div style="display: grid; gap: 1rem;">
      <craft-pane variant="plain">variant="plain" (default)</craft-pane>
      <craft-pane variant="error">variant="error"</craft-pane>
      <craft-pane variant="code" appearance="outline">
        <pre style="margin: 0;">
variant="code" appearance="outline"

Code panes scroll past 500px and use a quiet fill.</pre>
      </craft-pane>
    </div>
  `,
};

/**
 * A `code` pane clamps at 500px and scrolls its overflow.
 *
 * The scroll container lives in the shadow root, so the component makes *that*
 * element the tab stop — consumers never add `tabindex` to the pane. Tab to the
 * block below and the arrow keys, PageUp/PageDown, and Home/End scroll it;
 * focus the host instead and the browser would scroll the page, since it only
 * ever scrolls the focused element or a scrollable ancestor of it.
 *
 * The tab stop only appears while the content actually overflows, the focus
 * ring traces the pane's rounded corners, and the well stays flat — the `code`
 * variant drops the shadow it would otherwise inherit from `appearance`.
 */
export const ScrollableCode: Story = {
  render: () => html`
    <craft-pane variant="code" aria-label="Loaded project config">
      <pre style="margin: 0;">${longYaml}</pre>
    </craft-pane>
  `,
  play: async ({canvasElement}) => {
    const pane = canvasElement.querySelector('craft-pane') as CraftPane;
    await customElements.whenDefined('craft-pane');
    await pane.updateComplete;

    const scroller = pane.shadowRoot!.querySelector('.cp-pane') as HTMLElement;

    // Overflowing content puts the scroll container in the tab order.
    await waitFor(() => expect(scroller).toHaveAttribute('tabindex', '0'));
    await expect(scroller).toHaveAttribute('role', 'region');
    await expect(scroller).toHaveAttribute(
      'aria-label',
      'Loaded project config'
    );

    // The focusable element is the scroll container itself…
    await expect(scroller.scrollHeight).toBeGreaterThan(scroller.clientHeight);
    await expect(getComputedStyle(scroller).overflowY).toBe('auto');

    // …and the host around it is not one, so a tab stop there would scroll the
    // page rather than the code.
    await expect(getComputedStyle(pane).overflowY).toBe('visible');
    await expect(pane).not.toHaveAttribute('tabindex');

    // Focus lands on the scroller, which is also the element carrying the
    // border radius the focus ring traces.
    pane.focus();
    await expect(pane.shadowRoot!.activeElement).toBe(scroller);
    await expect(getComputedStyle(scroller).borderTopLeftRadius).not.toBe(
      '0px'
    );

    // A code well is flat, even under the `raised` default appearance.
    await expect(getComputedStyle(scroller).boxShadow).toBe('none');

    // And the focused element is the one that moves.
    scroller.scrollTop = 120;
    await expect(scroller.scrollTop).toBeGreaterThan(0);
  },
};

/**
 * `padding` takes the `sm`/`md`/`lg`/`xl` steps, `0`, a unitless number
 * (pixels), or any CSS length.
 */
export const Padding: Story = {
  render: () => html`
    <div style="display: grid; gap: 1rem;">
      <craft-pane padding="sm">padding="sm"</craft-pane>
      <craft-pane padding="md">padding="md"</craft-pane>
      <craft-pane padding="lg">padding="lg" (default)</craft-pane>
      <craft-pane padding="xl">padding="xl"</craft-pane>
      <craft-pane padding="24">padding="24" (24px)</craft-pane>
      <craft-pane padding="0">
        <div
          style="padding: 0.5rem; background: var(--c-color-neutral-fill-quiet);"
        >
          padding="0" — edge-to-edge content, e.g. a table
        </div>
      </craft-pane>
    </div>
  `,
};

/** The `label` attribute renders an `<h1>` and brings in the header region. */
export const WithHeader: Story = {
  args: {
    label: 'General Settings',
  },
};

/** Anything in `header-actions` sits at the end of the header. */
export const WithHeaderActions: Story = {
  render: () => html`
    <craft-pane label="System Messages">
      <craft-button slot="header-actions" appearance="outline">
        <craft-icon slot="prefix" name="plus"></craft-icon>
        New message
      </craft-button>
      ${bodyCopy}
    </craft-pane>
  `,
};

/** The `title` slot replaces the default `<h1>` while keeping the layout. */
export const CustomTitle: Story = {
  render: () => html`
    <craft-pane>
      <span
        slot="title"
        style="display: inline-flex; align-items: center; gap: 0.5em;"
      >
        <craft-icon name="newspaper"></craft-icon>
        <strong>Articles</strong>
        <code>articles</code>
      </span>
      ${bodyCopy}
    </craft-pane>
  `,
};

/**
 * The footer's default layout puts `feedback` at the start and the
 * `secondary-action` + `primary-action` group at the end.
 */
export const WithFooter: Story = {
  render: () => html`
    <craft-pane>
      ${bodyCopy}
      <span slot="feedback">All changes saved</span>
      <craft-button slot="secondary-action" appearance="plain">
        Cancel
      </craft-button>
      <craft-button slot="primary-action" variant="accent">Save</craft-button>
    </craft-pane>
  `,
};

/** `actions` replaces the whole secondary/primary group. */
export const WithFooterActions: Story = {
  render: () => html`
    <craft-pane>
      ${bodyCopy}
      <div
        slot="actions"
        style="display: flex; gap: 0.5rem; margin-inline-start: auto;"
      >
        <craft-button appearance="plain">Discard</craft-button>
        <craft-button appearance="outline">Save and continue</craft-button>
        <craft-button variant="accent">Save</craft-button>
      </div>
    </craft-pane>
  `,
};

/** Header, body, and footer together. */
export const FullComposition: Story = {
  render: () => html`
    <craft-pane label="Entry Types" padding="lg">
      <craft-button slot="header-actions" appearance="outline">
        <craft-icon slot="prefix" name="plus"></craft-icon>
        New entry type
      </craft-button>

      ${bodyCopy}

      <p style="margin-block-start: 0.5rem;">
        The header and footer are sticky, so they stay put while the body
        scrolls inside a height-constrained pane.
      </p>

      <span slot="feedback">Last saved 2 minutes ago</span>
      <craft-button slot="secondary-action" appearance="plain">
        Cancel
      </craft-button>
      <craft-button slot="primary-action" variant="accent">Save</craft-button>
    </craft-pane>
  `,
};

/**
 * The `header`, `body`, and `footer` slots replace their whole region —
 * padding included — for panes that need full control of the chrome.
 */
export const CustomRegions: Story = {
  render: () => html`
    <craft-pane padding="0">
      <div
        slot="header"
        style="display: flex; justify-content: space-between; padding: 1rem; border-block-end: 1px solid var(--c-color-neutral-border-quiet);"
      >
        <strong>Custom header</strong>
        <span>replaces the default layout</span>
      </div>

      <div slot="body" style="padding: 1rem;">
        A slotted <code>body</code> replaces the padded body wrapper, so the
        content controls its own spacing.
      </div>

      <div
        slot="footer-content"
        style="display: flex; justify-content: center; padding: 0.5rem 1rem;"
      >
        <code>footer-content</code> keeps the footer chrome but replaces its
        contents.
      </div>
    </craft-pane>
  `,
};

/** Surface treatment is themeable per pane via `--c-pane-*` properties. */
export const CustomProperties: Story = {
  render: () => html`
    <craft-pane
      style="--c-pane-background: var(--c-color-neutral-fill-quiet); --c-pane-radius: 0; --c-pane-shadow: none;"
    >
      Square corners, no shadow, and a quiet fill via
      <code>--c-pane-*</code> custom properties.
    </craft-pane>
  `,
};
