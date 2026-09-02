import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './truncate.js';
import type CraftTruncate from './truncate.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `truncate.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftTruncate>('craft-truncate');

type TruncateArgs = CraftTruncate & typeof args;

const LONG_TEXT =
  'This is a fairly long piece of text that will be truncated when it does not fit';

/**
 * The component truncates against its container, so every story needs one with
 * a width. The box is story scaffolding, not part of the component.
 */
const inABox = (content: unknown, width = '200px') => html`
  <div
    style="width: ${width}; border: 1px dashed var(--c-color-border-quiet, #ccc)"
  >
    ${content}
  </div>
`;

const meta = {
  title: 'Components/Truncate',
  component: 'craft-truncate',
  args: {...args, 'default-slot': LONG_TEXT},
  argTypes,
  parameters: {layout: 'centered'},
  // Render from args alone so every control — attributes and slot — drives the
  // story, inside the constrained box the component needs to truncate at all.
  render: (args) => inABox(template(args)),
} satisfies Meta<TruncateArgs>;

export default meta;
type Story = StoryObj<TruncateArgs>;

/** Text too long for its container truncates, and gains a tooltip. */
export const Default: Story = {};

/** Text that fits is left alone, and gets no tooltip. */
export const FitsWithoutTooltip: Story = {
  args: {'default-slot': 'Short label'},
  render: (args) => inABox(template(args), '400px'),
};

/** `placement` moves the tooltip, the same values the overlay takes. */
export const Placement: Story = {
  args: {placement: 'bottom'},
};

/** `disabled` keeps the visual truncation but drops the tooltip. */
export const Disabled: Story = {
  args: {disabled: true},
};
