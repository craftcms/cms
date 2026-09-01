import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './input-password.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Controls/Input Password',
  component: 'craft-input-password',
  args: {},
  render: function () {
    return html`<craft-input-password
      label="Password"
      help-text="Click the eye icon to show or hide the input"
      value="SuperSecurePassword123"
    ></craft-input-password>`;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {
  args: {},
};
