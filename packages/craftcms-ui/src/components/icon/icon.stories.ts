import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './icon.js';
import type CraftIcon from './icon.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `icon.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} = getStorybookHelpers<CraftIcon>('craft-icon');

type IconArgs = CraftIcon & typeof args;

const meta = {
  title: 'Components/Icon',
  component: 'craft-icon',
  args: {...args, name: 'chevron-down'},
  argTypes,
  // Render from args alone so every control drives the story.
  render: (args) => template(args),
} satisfies Meta<IconArgs>;

export default meta;
type Story = StoryObj<IconArgs>;

/** An icon named from the CP's published icon set. */
export const Default: Story = {};

/**
 * Icons inherit `currentColor` and are sized in `em`, so they take the colour
 * and size of the text around them rather than needing either set.
 */
export const InheritsText: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 0.75rem">
      <p style="margin: 0">
        Default size <craft-icon name="star"></craft-icon>
      </p>
      <p style="margin: 0; font-size: 1.5rem">
        Larger text <craft-icon name="star"></craft-icon>
      </p>
      <p style="margin: 0; color: var(--c-color-danger-on-normal)">
        Coloured text <craft-icon name="star"></craft-icon>
      </p>
    </div>
  `,
};

/**
 * `label` gives the icon an accessible name and makes it `role="img"`. Without
 * one it is `aria-hidden`, which is what you want beside text that already
 * says the same thing.
 */
export const Labelled: Story = {
  args: {name: 'circle-exclamation', label: 'Warning'},
};

/**
 * `appearance="badge"` draws the icon on a coloured disc, for a marker that
 * has to read as a status. `data-color` picks the colour.
 */
export const Badge: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; gap: 0.75rem; align-items: center">
      <craft-icon name="circle-exclamation" appearance="badge"></craft-icon>
      <craft-icon
        name="circle-check"
        appearance="badge"
        data-color="success"
      ></craft-icon>
      <craft-icon
        name="triangle-exclamation"
        appearance="badge"
        data-color="danger"
      ></craft-icon>
    </div>
  `,
};

/**
 * Slot your own `<svg>` when the icon set does not cover what you need. The
 * named icon is not fetched while the slot has content.
 */
export const SlottedSvg: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-icon>
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M12 2 15 9l7 .5-5.5 4.5L18 22l-6-3.5L6 22l1.5-8L2 9.5 9 9z" />
      </svg>
    </craft-icon>
  `,
};
