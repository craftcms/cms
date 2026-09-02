import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './reorder-button.js';
import type CraftReorderButton from './reorder-button.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `reorder-button.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} = getStorybookHelpers<CraftReorderButton>(
  'craft-reorder-button'
);

type ReorderButtonArgs = CraftReorderButton & typeof args;

const meta = {
  title: 'Components/Reorder Button',
  component: 'craft-reorder-button',
  args: {
    ...args,
    label: 'Reorder',
    position: 'middle',
    variant: 'neutral',
    orientation: 'vertical',
  },
  argTypes,
  parameters: {layout: 'centered'},
  // Render from args alone so every control drives the story. Stories below
  // vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<ReorderButtonArgs>;

export default meta;
type Story = StoryObj<ReorderButtonArgs>;

/** Both directions available, for an item in the middle of a list. */
export const Default: Story = {};

export const First: Story = {
  name: 'First (move-up disabled)',
  args: {position: 'first'},
};

export const Last: Story = {
  name: 'Last (move-down disabled)',
  args: {position: 'last'},
};

export const Horizontal: Story = {
  name: 'Horizontal (move forward/backward)',
  args: {orientation: 'horizontal'},
};

export const Disabled: Story = {
  args: {disabled: true},
};
