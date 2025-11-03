import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './button-group.js';
import '../button/button.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Button Group',
  component: 'craft-button-group',
  argTypes: {},
  render: (args) => html`
    <craft-button-group>
      <craft-button>One</craft-button>
      <craft-button>Two</craft-button>
      <craft-button>Three</craft-button>
    </craft-button-group>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};
