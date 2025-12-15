import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import {html} from 'lit';

import './shortcut.js';
import type CraftShortcut from './shortcut';

const {events, args, argTypes, template} =
  getStorybookHelpers('craft-shortcut');

const meta: Meta<CraftShortcut> = {
  title: 'Components/Shortcut',
  component: 'craft-shortcut',
  args,
  argTypes,
  parameters: {
    actions: {
      handles: events,
    },
  },
  render({shift, alt, os}) {
    return html`<craft-shortcut ?shift="${shift}" ?alt="${alt}" os="${os}">S</craft-shortcut>`;
  },
} satisfies Meta<CraftShortcut>;

export default meta;
type Story = StoryObj<CraftShortcut>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};
