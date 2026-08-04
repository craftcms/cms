import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './spinner.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Spinner',
  component: 'craft-spinner',
  argTypes: {
    width: {},
    size: {
      control: {
        type: 'text',
      },
      defaultValue: null,
    },
  },
  render: function ({size, width, message}) {
    let style = '';
    if (size) {
      style = `--size: ${size};`;
    }

    if (width) {
      style += `--width: ${width};`;
    }

    return html`<craft-spinner style="${style}">${message}</craft-spinner>`;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Basic: Story = {
  args: {},
};

export const Size: Story = {
  args: {
    size: '100px',
    width: '4px',
  },
};

export const Message: Story = {
  args: {
    message: 'This is a message',
  },
};
