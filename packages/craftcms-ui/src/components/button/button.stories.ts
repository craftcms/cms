import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './button.js';
import '../icon/icon.js';
import '../chip/chip.js';
import {ButtonVariant, ButtonAppearance} from '@src/components/button/button';

const buttonVariants = Object.values(ButtonVariant);
const appearance = Object.values(ButtonAppearance);

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

export const Variants: Story = {
  args: {
    variant: 'accent',
  },
  argTypes: {
    variant: {
      control: {type: 'select'},
      options: buttonVariants,
    },
  },
  render: () =>
    html`${buttonVariants.map(
      (variant) => html`
        <craft-button variant="${variant}">${variant}</craft-button>
      `
    )}`,
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

export const Links: Story = {
  args: {},
  render: () => html`
    <div class="grid gap-4">
      <div class="flex gap-2 items-center">
        ${appearance.map(
          (a) => html`
            <craft-button appearance="${a}" href="#" variant="accent"
              >${a} link</craft-button
            >
          `
        )}
      </div>
      <div class="flex gap-2 items-center">
        ${['zero', 'small', 'medium', 'large'].map(
          (size) =>
            html`<craft-button href="#" size="${size}">${size}</craft-button>`
        )}
      </div>
      <div class="flex gap-2 items-center">
        <craft-button href="https://craftcms.com" target="_blank"
          >New tab</craft-button
        >
        <craft-button href="/file.zip" download="file.zip"
          >Download</craft-button
        >
        <craft-button href="#" disabled>Disabled link</craft-button>
      </div>
    </div>
  `,
};
