import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './button.js';
import '../icon/icon.js';
import '../chip/chip.js';
import {ButtonVariant} from '@src/components/button/button';

const buttonVariants = Object.values(ButtonVariant);

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Button',
  component: 'craft-button',
  parameters: {
    layout: 'centered',
  },
  args: {
    label: 'Button',
    loading: false,
    variant: ButtonVariant.Fill,
  },
  argTypes: {
    variant: {
      control: {type: 'select'},
      options: buttonVariants,
    },
    loading: {
      control: {type: 'boolean'},
    },
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

/**
 * Every variant. `primary` and `danger` are solid and colored; the rest are the
 * neutral palette in their named appearance.
 */
export const Default: Story = {
  render: () => html`
    <div class="flex gap-2 items-center flex-wrap">
      ${buttonVariants.map(
        (variant) => html`
          <craft-button variant="${variant}">${variant}</craft-button>
        `
      )}
    </div>
  `,
};

/**
 * With the `inherit` property, the neutral variants adopt the ambient colorable
 * palette (any `[data-color]` / colorable ancestor, e.g. a callout). `primary`
 * and `danger` keep their own colors regardless.
 */
export const Inherit: Story = {
  render: () => html`
    <div class="grid gap-4">
      ${['accent', 'violet', 'success'].map(
        (color) => html`
          <div
            data-color="${color}"
            class="flex gap-2 items-center flex-wrap"
            style="padding: 0.75rem; border-radius: 8px; background: var(--c-color-fill-quiet);"
          >
            ${[
              ButtonVariant.Solid,
              ButtonVariant.Fill,
              ButtonVariant.Outline,
              ButtonVariant.Dashed,
              ButtonVariant.Plain,
              ButtonVariant.Link,
            ].map(
              (variant) => html`
                <craft-button variant="${variant}" inherit
                  >${variant}</craft-button
                >
              `
            )}
            <craft-button variant="${ButtonVariant.Primary}"
              >primary</craft-button
            >
            <craft-button variant="${ButtonVariant.Danger}"
              >danger</craft-button
            >
          </div>
        `
      )}
    </div>
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

export const Icons: Story = {
  render: () => html`
    <div class="flex gap-2 items-center flex-wrap">
      <craft-button icon="location">Prefix icon</craft-button>
      <craft-button icon="chevron-down" icon-position="suffix"
        >Suffix icon</craft-button
      >
      <craft-button>
        <craft-icon slot="prefix" name="location"></craft-icon>
        Slotted prefix
        <craft-icon slot="suffix" name="chevron-down"></craft-icon>
      </craft-button>
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
        ${buttonVariants.map(
          (variant) => html`
            <craft-button variant="${variant}" href="#"
              >${variant} link</craft-button
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
