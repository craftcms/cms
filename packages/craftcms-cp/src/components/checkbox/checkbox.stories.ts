import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './checkbox.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Controls/Checkbox',
  component: 'craft-checkbox',
  args: {},
  render: function () {
    return html`<craft-checkbox
      label="Craft Input"
      help-text="This is some instructions text"
    ></craft-checkbox>`;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

export const NoLabel: Story = {
  render: function () {
    return html`<craft-checkbox
      label="Craft Input"
      help-text="This is some instructions text"
      label-sr-only
    ></craft-checkbox>`;
  },
};
