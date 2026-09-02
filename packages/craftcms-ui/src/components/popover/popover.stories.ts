import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';

import './popover.js';

const meta = {
  title: 'Components/Popover',
  component: 'craft-popover',
  parameters: {
    layout: 'centered',
  },
  argTypes: {
    placement: {
      control: {type: 'select'},
      options: [
        'top',
        'top-start',
        'top-end',
        'bottom',
        'bottom-start',
        'bottom-end',
        'left',
        'left-start',
        'left-end',
        'right',
        'right-start',
        'right-end',
      ],
    },
    matchInvokerWidth: {
      control: {type: 'boolean'},
    },
  },
  render: (args) => html`
    <craft-popover
      placement="${args.placement || 'bottom-start'}"
      ?match-invoker-width="${args.matchInvokerWidth}"
    >
      <button slot="invoker" type="button">Toggle Popover</button>
      <div slot="content">
        <p>Popover content goes here.</p>
        <p>It positions itself relative to the invoker.</p>
      </div>
    </craft-popover>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

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
