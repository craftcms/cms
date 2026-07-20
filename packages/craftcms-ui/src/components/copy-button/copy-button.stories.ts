import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './copy-button.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Functional/Copy Button',
  component: 'craft-copy-button',
  parameters: {
    layout: 'centered',
  },
  argTypes: {},
  render: (args) => html`
    <div style="display: grid; gap: 1em;">
      <div>
        <craft-copy-button value="You copied me!" data-testId="button"
          >Clicking will copy "You copied me!" to the clipboard.
        </craft-copy-button>
      </div>
      <div>
        <input
          type="text"
          value=""
          placeholder="Test your paste here"
          data-testId="pastezone"
        />
      </div>
    </div>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};
