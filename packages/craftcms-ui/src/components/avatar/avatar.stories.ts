import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './avatar.js';
import type CraftAvatar from './avatar.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftAvatar>('craft-avatar');

type AvatarArgs = CraftAvatar & typeof args;

const meta = {
  title: 'Components/Avatar',
  component: 'craft-avatar',
  args: {...args, label: 'Brad Bell'},
  argTypes,
  parameters: {layout: 'centered'},
  render: (args) => template(args),
} satisfies Meta<AvatarArgs>;

export default meta;
type Story = StoryObj<AvatarArgs>;

/** Initials are derived from the label — one letter per word. */
export const Default: Story = {};

/** A single-word label gives a single initial. */
export const SingleName: Story = {
  args: {label: 'Craft'},
};

/** Without a label the avatar falls back to a question mark. */
export const NoLabel: Story = {
  args: {label: null},
};

/** Size and colours are custom properties, so a set can be themed at once. */
export const Themed: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; gap: 0.75rem; align-items: center">
      <craft-avatar label="Brad Bell"></craft-avatar>
      <craft-avatar label="Ryan Irelan" style="--size: 3rem"></craft-avatar>
      <craft-avatar
        label="Sara Fisher"
        style="--size: 3rem; --color-start: #7ab55c; --color-end: #2e7d5b"
      ></craft-avatar>
    </div>
  `,
};
