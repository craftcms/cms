import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './chip.js';
import '../status/status.js';
import '../button/button.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Chip',
  component: 'craft-chip',
  argTypes: {},
  render: (args) => html` <craft-chip> This is a chip </craft-chip> `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

export const PrefixAndSuffix: Story = {
  args: {},
  render: (args) => html`
    <craft-chip>
      <craft-status slot="prefix" status="live"></craft-status>
      This is a chip
      <craft-button icon size="small" appearance="plain" slot="suffix">
        <span class="icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
            <!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
            <path
              fill="currentColor"
              d="M96 320C96 289.1 121.1 264 152 264C182.9 264 208 289.1 208 320C208 350.9 182.9 376 152 376C121.1 376 96 350.9 96 320zM264 320C264 289.1 289.1 264 320 264C350.9 264 376 289.1 376 320C376 350.9 350.9 376 320 376C289.1 376 264 350.9 264 320zM488 264C518.9 264 544 289.1 544 320C544 350.9 518.9 376 488 376C457.1 376 432 350.9 432 320C432 289.1 457.1 264 488 264z"
            />
          </svg>
        </span>
      </craft-button>
    </craft-chip>
  `,
};

export const PrefixOnly: Story = {
  args: {},
  render: (args) => html`
    <craft-chip>
      <craft-status slot="prefix" status="live"></craft-status>
      This is a chip
    </craft-chip>
  `,
};

export const SuffixOnly: Story = {
  args: {},
  render: (args) => html`
    <craft-chip>
      <craft-button icon size="small" appearance="plain" slot="suffix">
        <span class="icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
            <!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
            <path
              fill="currentColor"
              d="M96 320C96 289.1 121.1 264 152 264C182.9 264 208 289.1 208 320C208 350.9 182.9 376 152 376C121.1 376 96 350.9 96 320zM264 320C264 289.1 289.1 264 320 264C350.9 264 376 289.1 376 320C376 350.9 350.9 376 320 376C289.1 376 264 350.9 264 320zM488 264C518.9 264 544 289.1 544 320C544 350.9 518.9 376 488 376C457.1 376 432 350.9 432 320C432 289.1 457.1 264 488 264z"
            />
          </svg>
        </span>
      </craft-button>
      This is a chip
    </craft-chip>
  `,
};
