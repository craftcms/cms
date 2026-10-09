import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './copy-button.js';
import type CraftCopyButton from './copy-button.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `copy-button.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftCopyButton>('craft-copy-button');

type CopyButtonArgs = CraftCopyButton & typeof args;

/** A field to paste into, so the copy can actually be checked. */
const withPasteTarget = (button: unknown) => html`
  <div style="display: grid; gap: 1em">
    <div>${button}</div>
    <input
      type="text"
      placeholder="Test your paste here"
      aria-label="Paste here"
    />
  </div>
`;

const meta = {
  title: 'Components/Copy Button',
  component: 'craft-copy-button',
  args: {
    ...args,
    value: 'You copied me!',
    'default-slot': 'Copy to clipboard',
  },
  argTypes,
  parameters: {layout: 'centered'},
  // Render from args alone so every control — attributes and slot — drives the
  // story. Stories below vary the args, not the template.
  render: (args) => withPasteTarget(template(args)),
} satisfies Meta<CopyButtonArgs>;

export default meta;
type Story = StoryObj<CopyButtonArgs>;

/** Clicking copies `value` and shows the outcome for a moment. */
export const Default: Story = {};

/** `tooltip-label` names the button when its content is an icon. */
export const WithTooltipLabel: Story = {
  args: {'tooltip-label': 'Copy the handle', 'default-slot': 'Copy'},
};

/** `feedback-duration` sets how long the outcome stays up, in milliseconds. */
export const SlowFeedback: Story = {
  args: {'feedback-duration': 4000},
};

export const Disabled: Story = {
  args: {disabled: true},
};
