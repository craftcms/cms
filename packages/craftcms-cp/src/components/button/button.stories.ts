import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './button.js';
import '../icon/icon.js';
import '../chip/chip.js';

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
            <craft-button variant="${variant}"
              >${variant ?? 'None'}</craft-button
            >
            <craft-button appearance="filled" variant="${variant}"
              >${variant} filled</craft-button
            >
            <craft-button appearance="dashed" variant="${variant}"
              >${variant} dashed</craft-button
            >
            <craft-button appearance="plain" variant="${variant}"
              >${variant} plain</craft-button
            >
          </div>
        `
      )}

      <craft-chip data-color="violet">
        <div class="flex gap-2">
          <craft-button variant="inherit">Chip buttons</craft-button>
          <craft-button appearance="filled" variant="inherit"
            >Filled</craft-button
          >
          <craft-button appearance="dashed" variant="inherit"
            >Dashed</craft-button
          >
          <craft-button appearance="plain" variant="inherit">lain</craft-button>
        </div>
      </craft-chip>
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
        <craft-icon name="location"></craft-icon>
      </craft-button>
      <craft-button icon size="small">
        <craft-icon name="location"></craft-icon>
      </craft-button>
    </div>
  `,
};

export const Loading: Story = {
  args: {},
  render: (args) => html` <craft-button loading> Submit </craft-button> `,
};
