import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './button.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Button',
  component: 'craft-button',
  parameters: {
    layout: 'centered',
  },
  argTypes: {},
  render: (args) => html`
    <div class="grid gap-4">
      ${['primary', 'default', 'danger'].map(
        (variant) => html`
          <div class="flex gap-2">
            <craft-button variant="${variant}">${variant}</craft-button>
            <craft-button appearance="plain" variant="${variant}"
              >${variant} plain</craft-button
            >
          </div>
        `
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

export const Sizes: Story = {
  args: {},
  render: (args) => html`
    <div class="flex gap-2 items-center">
      ${['zero', 'small', 'medium', 'large'].map(
        (size) => html`<craft-button size="${size}">${size}</craft-button>`
      )}
    </div>
  `,
};

export const Icon: Story = {
  args: {},
  render: (args) => html`
    <div class="flex gap-2 items-center">
      <craft-button icon>
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
      <craft-button icon size="small">
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
    </div>
  `,
};

export const Loading: Story = {
  args: {},
  render: (args) => html` <craft-button loading> Submit </craft-button> `,
};
