import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './truncate.js';

const meta = {
  title: 'Components/Truncate',
  component: 'craft-truncate',
  args: {
    width: '200px',
    content:
      'This is a fairly long piece of text that will be truncated when it does not fit',
    placement: 'top',
  },
  argTypes: {
    width: {control: {type: 'text'}},
    content: {control: {type: 'text'}},
    placement: {
      control: {type: 'select'},
      options: [
        'top-start',
        'top',
        'top-end',
        'right-start',
        'right',
        'right-end',
        'bottom-end',
        'bottom',
        'bottom-start',
        'left-end',
        'left',
        'left-start',
      ],
    },
  },
  parameters: {
    layout: 'centered',
  },
  render: (args) => html`
    <div
      style="width: ${args.width}; border: 1px dashed var(--c-color-border-quiet, #ccc);"
    >
      <craft-truncate placement="${args.placement}"
        >${args.content}</craft-truncate
      >
    </div>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Playground: Story = {};

export const FitsWithoutTooltip: Story = {
  args: {
    width: '400px',
    content: 'Short label',
  },
};
