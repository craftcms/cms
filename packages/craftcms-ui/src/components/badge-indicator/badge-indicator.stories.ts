import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './badge-indicator.js';
import type CraftBadgeIndicator from './badge-indicator.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `badge-indicator.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} = getStorybookHelpers<CraftBadgeIndicator>(
  'craft-badge-indicator'
);

type BadgeIndicatorArgs = CraftBadgeIndicator & typeof args;

const meta = {
  title: 'Components/Badge Indicator',
  component: 'craft-badge-indicator',
  args: {...args, variant: 'primary', 'alt-text': 'Has notifications'},
  argTypes,
  // Render from args alone so every control drives the story. Stories below
  // vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<BadgeIndicatorArgs>;

export default meta;
type Story = StoryObj<BadgeIndicatorArgs>;

/** With no count, the indicator is a bare dot. */
export const Dot: Story = {};

/** A `badge-count` turns it into a number. */
export const Numbered: Story = {
  args: {
    'alt-text': null,
    'badge-count': 5,
    'badge-count-suffix': 'updates',
  },
};

/** The default variant, for the standard surface. */
export const Primary: Story = {
  args: {variant: 'primary', 'badge-count': 3},
};

/** A quieter variant, for a surface that already carries emphasis. */
export const Secondary: Story = {
  args: {variant: 'secondary', 'badge-count': 3},
};

/** An inverse indicator, for a dark surface. */
export const Inverse: Story = {
  args: {variant: 'inverse', 'badge-count': 12},
};
