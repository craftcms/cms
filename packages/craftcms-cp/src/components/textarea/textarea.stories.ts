import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './textarea.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Controls/Textarea',
  component: 'craft-textarea',
  args: {},
  render: function () {
    return html`<craft-textarea label="Craft Textarea" help-text="This is some instructions text"></craft-uinput>`;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};
