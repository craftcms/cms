import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

// import "@awesome.me/webawesome/dist/components/tooltip/tooltip.js";
import './tooltip.js';
import '../button/button.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Tooltip',
  component: 'craft-tooltip',
  args: {
    placement: 'top',
    content: 'This is some content within a tooltip',
  },
  parameters: {
    layout: 'centered',
  },
  render: function (args) {
    let style = '';
    if (args.maxWidth) {
      style = `--max-width: ${args.maxWidth};`;
    }

    return html`
      <craft-tooltip
        placement="${args.placement}"
        style="${args.style}"
        for="my-button"
        >${args.content}</craft-tooltip
      >
      <craft-button id="my-button">Hover me</craft-button>
    `;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Playground: Story = {
  args: {
    placement: 'top',
  },
  argTypes: {
    style: {
      control: {
        type: 'text',
      },
    },
    content: {
      control: {
        type: 'text',
      },
    },
    placement: {
      control: {
        type: 'select',
      },
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
      defaultValue: 'top',
    },
  },
};
