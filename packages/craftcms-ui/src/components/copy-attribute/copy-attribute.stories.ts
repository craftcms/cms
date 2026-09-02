import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './copy-attribute.js';
import type CraftCopyAttribute from './copy-attribute.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `copy-attribute.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} = getStorybookHelpers<CraftCopyAttribute>(
  'craft-copy-attribute'
);

type CopyAttributeArgs = CraftCopyAttribute & typeof args;

const meta = {
  title: 'Components/Copy Attribute',
  component: 'craft-copy-attribute',
  args: {...args, value: 'fieldHandle', 'default-slot': 'fieldHandle'},
  argTypes,
  parameters: {layout: 'centered'},
  // Render from args alone so every control — attributes and slot — drives the
  // story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<CopyAttributeArgs>;

export default meta;
type Story = StoryObj<CopyAttributeArgs>;

/** The value reads as text and copies on click. */
export const Default: Story = {};

/** The visible text and the copied value do not have to match. */
export const DifferentCopiedValue: Story = {
  args: {value: 'craft\\elements\\Entry', 'default-slot': 'Entry'},
};

export const Disabled: Story = {
  args: {disabled: true},
};
