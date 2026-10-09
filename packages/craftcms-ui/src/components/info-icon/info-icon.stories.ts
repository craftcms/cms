import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './info-icon.js';
import type CraftInfoIcon from './info-icon.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftInfoIcon>('craft-info-icon');

type InfoIconArgs = CraftInfoIcon & typeof args;

const meta = {
  title: 'Components/Info Icon',
  component: 'craft-info-icon',
  args: {
    ...args,
    'default-slot':
      'Entries in this section are only available on the sites you select.',
  },
  argTypes,
  parameters: {layout: 'centered'},
  render: (args) => template(args),
} satisfies Meta<InfoIconArgs>;

export default meta;
type Story = StoryObj<InfoIconArgs>;

/** Click the icon to reveal the explanation. */
export const Default: Story = {};

/** `icon` picks a different marker when the default is not the right one. */
export const CustomIcon: Story = {
  args: {icon: 'lightbulb', 'default-slot': 'A tip rather than a definition.'},
};

/** `disabled` stops the tooltip being opened. */
export const Disabled: Story = {
  args: {disabled: true},
};

/** Opening one info icon closes any other, so tooltips never accumulate. */
export const OnlyOneOpen: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; gap: 1.5rem">
      <craft-info-icon>The first explanation.</craft-info-icon>
      <craft-info-icon>The second explanation.</craft-info-icon>
      <craft-info-icon>The third explanation.</craft-info-icon>
    </div>
  `,
};

/** Beside the thing it explains, which is where it usually sits. */
export const InContext: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <span style="display: inline-flex; align-items: center; gap: 0.25rem">
      Propagation Method
      <craft-info-icon>
        How content is carried across the sites in this group when an entry is
        saved.
      </craft-info-icon>
    </span>
  `,
};
