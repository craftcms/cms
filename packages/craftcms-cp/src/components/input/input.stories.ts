import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './input.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Controls/Input',
  component: 'craft-input',
  args: {},
  render: function () {
    return html`<craft-input label="Craft Input" help-text="This is some instructions text"></craft-uinput>`;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};
