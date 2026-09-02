import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import {html} from 'lit';

import './popover.js';
import type CraftPopover from './popover.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `popover.ts` surfaces it here without touching this file.
 */
const {args, argTypes} = getStorybookHelpers<CraftPopover>('craft-popover');

type CraftPopoverArgs = CraftPopover & typeof args;

const meta = {
  title: 'Components/Popover',
  component: 'craft-popover',
  parameters: {
    layout: 'centered',
  },
  args: {...args, placement: 'bottom-start'},
  argTypes,
  render: (args) => html`
    <craft-popover
      placement="${args.placement || 'bottom-start'}"
      ?match-invoker-width="${args['match-invoker-width']}"
    >
      <button slot="invoker" type="button">Toggle Popover</button>
      <div slot="content">
        <p>Popover content goes here.</p>
        <p>It positions itself relative to the invoker.</p>
      </div>
    </craft-popover>
  `,
} satisfies Meta<CraftPopoverArgs>;

export default meta;
type Story = StoryObj<CraftPopoverArgs>;

export const Basic: Story = {
  args: {
    placement: 'bottom-start',
  },
};

export const BottomEnd: Story = {
  args: {
    placement: 'bottom-end',
  },
};

export const MatchInvokerWidth: Story = {
  args: {
    placement: 'bottom-start',
    matchInvokerWidth: true,
  },
  render: (args) => html`
    <craft-popover
      placement="${args.placement}"
      ?match-invoker-width="${args.matchInvokerWidth}"
    >
      <button slot="invoker" type="button" style="width: 200px;">
        Wide Invoker
      </button>
      <div slot="content">
        <p>This popover matches the invoker width.</p>
      </div>
    </craft-popover>
  `,
};
