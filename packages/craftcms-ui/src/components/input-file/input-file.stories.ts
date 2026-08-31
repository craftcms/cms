import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './input-file.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Form Controls/Input File',
  component: 'craft-input-file',
  args: {},
  render: function () {
    return html`<craft-input-file
      label="Upload File"
      help-text="Select a file to upload"
    ></craft-input-file>`;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

export const Multiple: Story = {
  render: function () {
    return html`<craft-input-file
      label="Upload Files"
      help-text="Select multiple files to upload"
      multiple
    ></craft-input-file>`;
  },
};

export const WithAccept: Story = {
  render: function () {
    return html`<craft-input-file
      label="Upload Image"
      help-text="Select an image file"
      accept="image/*"
    ></craft-input-file>`;
  },
};
