import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftSlideRule from './slide-rule.js';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import './slide-rule.js';

const {events, args, argTypes, template} =
  getStorybookHelpers('craft-slide-rule');

const meta: Meta<CraftSlideRule> = {
  title: 'Controls/Slide Rule',
  component: 'craft-slide-rule',
  args,
  argTypes,
  render: (args) => template(args),
  parameters: {
    actions: {
      handles: events,
    },
  },
};

export default meta;
type Story = StoryObj<CraftSlideRule & typeof args>;

export const Default: Story = {
  args: {},
};

export const StartingAngle: Story = {
  args: {
    value: 15,
  },
};
