import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './status.js';

const statuses = ['live', 'pending', 'expired', 'disabled'];

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Status',
  component: 'craft-status',
  argTypes: {},
  render: (args) => html`
    <div class="flex gap-2">
      ${statuses.map(
        (status) => html` <craft-status status="${status}"></craft-status>`
      )}
    </div>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};
