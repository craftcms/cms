import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './dropdown.js';
import '../button/button.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Dropdown',
  component: 'craft-dropdown',
  argTypes: {},
  render: (args) => html`
    <craft-dropdown>
      <craft-button slot="trigger">Dropdown</craft-button>

      <craft-dropdown-item>This is a thing</craft-dropdown-item>
      <craft-dropdown-item>Another thing</craft-dropdown-item>
      <craft-dropdown-item>Yet another thing</craft-dropdown-item>
    </craft-dropdown>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

export const Open: Story = {
  args: {
    open: true,
  },
};
