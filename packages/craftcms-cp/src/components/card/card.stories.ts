import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './card.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Card',
  component: 'craft-card',
  argTypes: {},
  render: (args) => html`
    <craft-card>
      Cards are really pretty boring, their mostly available as a convienience
      for layout.
    </craft-card>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};
