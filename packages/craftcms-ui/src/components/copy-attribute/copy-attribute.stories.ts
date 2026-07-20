import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './copy-attribute.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Copy Attribute',
  component: 'craft-copy-attribute',
  argTypes: {},
  parameters: {
    layout: 'centered',
  },
  render: (args) => html`
    <craft-copy-attribute value="fieldHandle">fieldHandle</craft-copy-attribute>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};
