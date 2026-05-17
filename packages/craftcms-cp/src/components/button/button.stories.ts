import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './button.js';
import '../icon/icon.js';
import '../chip/chip.js';

const buttonVariants = ['accent', 'neutral', 'danger'];
const appearance = ['solid', 'outline', 'plain'];

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Button',
  component: 'craft-button',
  parameters: {
    layout: 'centered',
  },
  args: {
    label: 'Button',
    appearance: 'solid',
    loading: false,
    variant: 'neutral',
  },
  argTypes: {
    appearance: {
      control: {type: 'select'},
      options: appearance,
    },
    loading: {
      control: {type: 'boolean'},
    },
  },
  render: (args) => html`
    <div class="grid gap-4">
      ${buttonVariants.map(
        (variant) => html`
          <div class="flex gap-2">
            <craft-button variant="${variant}"
              >${variant ?? 'None'} solid</craft-button
            >
            <craft-button appearance="outline" variant="${variant}"
              >${variant} outline</craft-button
            >
            <craft-button appearance="plain" variant="${variant}"
              >${variant} plain</craft-button
            >
          </div>
        `
      )}

      <craft-chip data-color="violet">
        <div class="flex gap-2">
          <craft-button variant="inherit">Chip Buttons</craft-button>
          <craft-button appearance="outline" variant="inherit"
            >Outline</craft-button
          >
          <craft-button appearance="plain" variant="inherit"
            >Plain</craft-button
          >
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

export const Accent: Story = {
  args: {
    variant: 'accent',
  },
  argTypes: {
    variant: {
      control: {type: 'select'},
      options: buttonVariants,
    },
  },
  render: (args) => html`
    <craft-button variant="${args.variant}">${args.label}</craft-button>
  `,
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
        <craft-icon name="location" label="Location"></craft-icon>
      </craft-button>
      <craft-button icon size="small">
        <craft-icon name="location" label="Location"></craft-icon>
      </craft-button>
    </div>
  `,
};

export const Loading: Story = {
  args: {
    loading: true,
  },
  render: (args) => html`
    <craft-button ?loading="${args.loading}"> Submit </craft-button>
  `,
};
